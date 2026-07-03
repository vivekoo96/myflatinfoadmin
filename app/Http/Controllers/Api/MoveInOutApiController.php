<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MoveInOutRequest;
use App\Models\Flat;
use App\Models\User;
use App\Models\Building;
use App\Models\BuildingUser;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use App\Helpers\AuthHelper;
use Carbon\Carbon;

class MoveInOutApiController extends Controller
{
    // BA lookup for owner details
    public function get_user_by_contact(Request $request)
    {
        $user = User::where('email', $request->email)->orWhere('phone', $request->phone)->first();
        if ($user) {
            return response()->json(['success' => true, 'user' => $user], 200);
        }
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }

    // Fetch user by email — for pre-filling tenant profile form
    public function fetch_user_by_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            return response()->json([
                'success'     => true,
                'is_existing' => true,
                'msg'         => 'User found.',
                'user'        => [
                    'id'         => $user->id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'gender'     => $user->gender,
                    'email'      => $user->email,
                    'phone'      => $user->phone,
                ],
            ], 200);
        }

        return response()->json([
            'success'     => true,
            'is_existing' => false,
            'msg'         => 'No user found with this email. You can create a new profile.',
            'user'        => null,
        ], 200);
    }

    // Step 1: Owner creates tenant profile (with all form fields)
    public function create_tenant_profile(Request $request)
    {
        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return response()->json(['error' => 'No building assigned to your account.'], 403);
        }

        $flat = AuthHelper::flat();
        if (!$flat) {
            return response()->json(['error' => 'No active flat associated with this token.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'first_name' => 'required_without:email|string',
            'last_name' => 'required_without:email|string',
            'phone' => 'nullable|string',
            'gender' => 'nullable|in:Male,Female,Other',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Verify flat belongs to this building and owner
        if ($flat->building_id != $building->id || $flat->owner_id != $user->id) {
            return response()->json(['error' => 'You do not own this flat or it does not belong to this building.'], 403);
        }

        // Verify block belongs to building
        $block = \App\Models\Block::where('id', $flat->block_id)
            ->where('building_id', $building->id)
            ->first();

        if (!$block) {
            return response()->json(['error' => 'Block not found in this building.'], 403);
        }

        $isExisting = false;

        // Check if user already exists by email
        $tenant = User::where('email', $request->email)->first();

        if ($tenant) {
            // User already exists — reuse their account
            $isExisting = true;
        } else {
            // Validate required fields for new user creation
            if (!$request->filled('first_name') || !$request->filled('last_name') || !$request->filled('phone')) {
                return response()->json(['error' => 'first_name, last_name and phone are required for new users.'], 422);
            }

            // Check phone uniqueness only for new users
            if (User::where('phone', $request->phone)->exists()) {
                return response()->json(['error' => 'The phone number is already taken.'], 422);
            }

            // Create new Tenant User Account
            $tenant = new User();
            $tenant->first_name = $request->first_name;
            $tenant->last_name = $request->last_name;
            $tenant->email = $request->email;
            $tenant->phone = $request->phone;
            $tenant->gender = $request->gender;
            $rawPassword = 'MFI@' . rand(1000, 9999);
            $tenant->password = Hash::make($rawPassword);
            $tenant->role = 'user';
            $tenant->status = 'Active';
            $tenant->building_id = $building->id;
            $tenant->created_by = $user->id; // The owner ID
            $tenant->created_type = 'direct';
            $tenant->save();

            // Send Email with password
            try {
                $setting = \App\Models\Setting::first();
                $logo = $setting ? $setting->logo : null;
                $info = array(
                    'user' => $tenant,
                    'password' => $rawPassword,
                    'logo' => $logo,
                );
                \Illuminate\Support\Facades\Mail::send('email.password', $info, function ($message) use ($tenant) {
                    $message->to($tenant->email, $tenant->first_name . ' ' . $tenant->last_name)
                            ->subject('Your MyFlatInfo Account Has Been Created');
                });
            } catch (\Exception $e) {
                \Log::error('Failed to send password email: ' . $e->getMessage());
            }
        }

        // Get or create building-specific 'user' role
        $userRole = Role::where('building_id', $building->id)
            ->where('slug', 'user')
            ->first();
        
        if (!$userRole) {
            $userRole = new Role();
            $userRole->building_id = $building->id;
            $userRole->name = 'User';
            $userRole->slug = 'user';
            $userRole->type = 'user';
            $userRole->save();
        }

        $alreadyAssigned = BuildingUser::where('user_id', $tenant->id)
            ->where('building_id', $building->id)
            ->where('role_id', $userRole->id)
            ->exists();

        if (!$alreadyAssigned) {
            // Check building limit
            $limit = (int) $building->no_of_logins;
            $activeCount = BuildingUser::where('building_id', $building->id)
                ->where('role_id', $userRole->id)
                ->where('status', 'Active')
                ->count();

            if ($activeCount >= $limit) {
                return response()->json(['error' => 'No of login limit is exceeded'], 422);
            }

            // Create building user relationship with Active status (just like admin code)
            $buildingUser = new BuildingUser();
            $buildingUser->building_id = $building->id;
            $buildingUser->user_id = $tenant->id;
            $buildingUser->role_id = $userRole->id;
            $buildingUser->status = 'Active';
            $buildingUser->save();
        }

        // Handle ID Proof file upload
        $id_proof = null;
        if ($request->hasFile('id_proof')) {
            $file = $request->file('id_proof');
            $filename = 'id_proofs/' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/move_in_out/'), $filename);
            $id_proof = $filename;
        }

        return response()->json([
            'success' => true,
            'is_existing' => $isExisting,
            'msg' => $isExisting ? 'Existing user assigned to building successfully.' : 'Tenant profile created successfully.',
            'user' => [
                'id' => $tenant->id,
                'first_name' => $tenant->first_name,
                'last_name' => $tenant->last_name,
                'gender' => $tenant->gender,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'flat_number' => $flat->name,
                'block' => $block->name,
                'id_proof' => $id_proof
            ]
        ], 201);
    }

    // Step 2: Owner creates move-in request for the tenant
    public function create_move_in_for_tenant(Request $request)
    {
        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return response()->json(['error' => 'No building assigned to your account.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'flat_id' => 'required|exists:flats,id',
            'preferred_move_in_date' => 'required|date',
            'additional_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $tenant = User::find($request->user_id);
        $flat = Flat::where('id', $request->flat_id)
            ->where('building_id', $building->id)
            ->where('owner_id', $user->id)
            ->first();

        if (!$flat) {
            return response()->json(['error' => 'Flat not found or you do not own this flat.'], 403);
        }

        // Verify tenant belongs to this building
        $tenantBuilding = BuildingUser::where('user_id', $tenant->id)
            ->where('building_id', $building->id)
            ->first();

        if (!$tenantBuilding) {
            return response()->json(['error' => 'Tenant is not assigned to this building.'], 403);
        }

        // Create Move-In Request
        $moveRequest = new MoveInOutRequest();
        $moveRequest->building_id = $building->id;
        $moveRequest->flat_id = $flat->id;
        $moveRequest->user_id = $tenant->id;
        $moveRequest->type = 'Move-In';
        $moveRequest->person_type = 'Tanent';
        $moveRequest->first_name = $tenant->first_name;
        $moveRequest->last_name = $tenant->last_name;
        $moveRequest->email = $tenant->email;
        $moveRequest->phone = $tenant->phone;
        $moveRequest->date_of_entry_exit = $request->preferred_move_in_date;
        $moveRequest->comment = $request->additional_notes;
        $moveRequest->status = 'Pending';

        // Handle ID proof passed from Step 1 or uploaded directly
        if ($request->hasFile('id_proof')) {
            $file = $request->file('id_proof');
            $filename = 'id_proofs/' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/move_in_out/'), $filename);
            $moveRequest->id_proof = $filename;
        } elseif ($request->filled('id_proof')) {
            $moveRequest->id_proof = $request->id_proof;
        }

        $moveRequest->save();

        return response()->json([
            'msg' => 'Move-In request submitted to BA for approval.',
            'request_id' => $moveRequest->id,
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->first_name . ' ' . $tenant->last_name,
                'flat' => $flat->name,
                'move_in_date' => $request->preferred_move_in_date
            ]
        ], 200);
    }

    // Legacy: Owner creates tanent and move-in request (combined - for backward compatibility)
    public function create_tanent_move_in(Request $request)
    {
        $user = Auth::user();
        $flat = AuthHelper::flat();

        if (!$flat || $flat->owner_id != $user->id) {
            return response()->json(['error' => 'You must be the owner of this flat to create a tanent.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'date_of_entry' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // 1. Create or Find Tanent User
        $tanent = User::where('email', $request->email)->orWhere('phone', $request->phone)->first();
        if (!$tanent) {
            $tanent = new User();
            $tanent->first_name = $request->first_name;
            $tanent->last_name = $request->last_name;
            $tanent->email = $request->email;
            $tanent->phone = $request->phone;
            $tanent->password = Hash::make('MFI@' . rand(1000, 9999));
            $tanent->role = 'user';
            $tanent->status = 'Active';
            $tanent->save();

            // Assign role to building
            $userRole = Role::where('name', 'User')->first();
            if ($userRole) {
                BuildingUser::create([
                    'user_id' => $tanent->id,
                    'building_id' => $flat->building_id,
                    'role_id' => $userRole->id
                ]);
            }
        }

        // 2. Create Move-In Request
        $moveRequest = new MoveInOutRequest();
        $moveRequest->building_id = $flat->building_id;
        $moveRequest->flat_id = $flat->id;
        $moveRequest->user_id = $tanent->id;
        $moveRequest->type = 'Move-In';
        $moveRequest->person_type = 'Tanent';
        $moveRequest->from_date = $request->from_date;
        $moveRequest->to_date = $request->to_date;
        $moveRequest->date_of_entry_exit = $request->date_of_entry;
        $moveRequest->status = 'Pending';

        if ($request->hasFile('id_proof')) {
            $file = $request->file('id_proof');
            $filename = 'id_proofs/' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/move_in_out/'), $filename);
            $moveRequest->id_proof = $filename;
        }

        $moveRequest->save();

        return response()->json(['msg' => 'Move-In request submitted to BA for approval.', 'request_id' => $moveRequest->id], 200);
    }

    // Owner or Tanent creates move-out request
    public function create_move_out_request(Request $request)
    {
        $user = Auth::user();
        $flat = AuthHelper::flat();

        if (!$flat) {
            return response()->json(['error' => 'No active flat found.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'date_of_exit' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $moveRequest = new MoveInOutRequest();
        $moveRequest->building_id = $flat->building_id;
        $moveRequest->flat_id = $flat->id;
        $moveRequest->user_id = $user->id;
        $moveRequest->type = 'Move-Out';
        
        $isTenant = ($flat->tanent_id == $user->id);
        $moveRequest->person_type = $isTenant ? 'Tanent' : 'Owner';
        $moveRequest->date_of_entry_exit = $request->date_of_exit;
        
        // If tenant, needs owner approval first
        $moveRequest->status = $isTenant ? 'Pending Owner' : 'Pending Accounts';
        $moveRequest->save();

        if ($isTenant) {
            $msg = 'Move-Out request submitted. Waiting for Owner approval.';
            // Notify owner
            if ($flat->owner) {
                NotificationHelper::sendNotification($flat->owner_id, 'Move-Out Request', "Your tenant has requested a move-out for flat {$flat->name}.", ['type' => 'MOVE_OUT_REQ', 'id' => $moveRequest->id]);
            }
        } else {
            $msg = 'Move-Out request submitted to Accounts for verification.';
        }

        return response()->json(['msg' => $msg, 'request_id' => $moveRequest->id], 200);
    }

    // Owner approves tenant move-out
    public function owner_approve_move_out(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:move_in_out_requests,id',
            'action' => 'required|in:Approve,Reject'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $moveRequest = MoveInOutRequest::find($request->request_id);
        $flat = $moveRequest->flat;

        if ($flat->owner_id != $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        if ($request->action == 'Approve') {
            $moveRequest->status = 'Pending Accounts';
            $msg = 'Approved and sent to Accounts.';
        } else {
            $moveRequest->status = 'Rejected';
            $msg = 'Request rejected.';
        }

        $moveRequest->save();
        return response()->json(['msg' => $msg], 200);
    }

    // Security: Fetch active requests for tabs
    public function get_security_requests(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:Move-In,Move-Out',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $user = Auth::user();
        $buildingId = $request->building_id; // Security should pass their building_id
        $sortOrder = $request->input('sort_order', 'desc');

        $requests = MoveInOutRequest::where('building_id', $buildingId)
            ->where('type', $request->type)
            ->where('status', 'Approved')
            ->whereNull('visited_at')
            ->with(['flat', 'user'])
            ->orderBy('created_at', $sortOrder)
            ->get();

        return response()->json(['success' => true, 'requests' => $requests], 200);
    }

    // Fetch active passcode for user
    public function get_active_passcode()
    {
        $user = Auth::user();
        $pass = MoveInOutRequest::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->whereNull('visited_at')
            ->with(['flat.block'])
            ->first();

        if ($pass) {
            $name = $pass->user ? $pass->user->name : trim($pass->first_name . ' ' . $pass->last_name);

            $responseData = [
                'success' => true,
                'id' => $pass->id,
                'passcode' => $pass->passcode,
                'type' => $pass->type,
                'person_type' => $pass->person_type,
                'date_of_entry_exit' => $pass->date_of_entry_exit,
                'flat' => $pass->flat ? [
                    'name' => $pass->flat->name,
                    'block' => $pass->flat->block ? $pass->flat->block->name : null,
                ] : null,
            ];

            if ($pass->person_type === 'Owner') {
                $responseData['owner_name'] = $name;
            } else {
                $responseData['tenant_name'] = $name;
                $responseData['tanent_name'] = $name;
            }

            return response()->json($responseData, 200);
        }
        return response()->json(['success' => false, 'message' => 'No active passcode found.'], 404);
    }

    // Security: Verify Passcode
    public function verify_passcode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'passcode' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $pass = MoveInOutRequest::where('passcode', $request->passcode)
            ->where('status', 'Approved')
            ->with(['flat', 'user'])
            ->first();

        if (!$pass) {
            return response()->json(['error' => 'Invalid or expired passcode.'], 404);
        }

        // Validate type if optional type filter is passed
        if ($request->filled('type') && in_array($request->type, ['Move-In', 'Move-Out'])) {
            if ($pass->type !== $request->type) {
                return response()->json(['error' => "This passcode is for a {$pass->type} request, but you are verifying a {$request->type}."], 422);
            }
        }

        return response()->json([
            'success' => true,
            'request' => $pass,
            'id_proof_url' => $pass->id_proof ? asset('images/move_in_out/' . $pass->id_proof) : null,
            'user' => $pass->user ? [
                'name' => $pass->user->name,
                'phone' => $pass->user->phone,
                'email' => $pass->user->email,
            ] : [
                'name' => $pass->first_name . ' ' . $pass->last_name,
                'phone' => $pass->phone,
                'email' => $pass->email,
            ]
        ], 200);
    }

    // Security: Submit Entry/Exit
    public function submit_entry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'passcode' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $pass = MoveInOutRequest::where('passcode', $request->passcode)
            ->where('status', 'Approved')
            ->first();

        if (!$pass) {
            return response()->json(['error' => 'Invalid or expired passcode.'], 404);
        }

        $pass->status = 'Completed';
        $pass->visited_at = now();
        $pass->save();

        $flat = Flat::find($pass->flat_id);
        if ($flat) {
            if ($pass->type == 'Move-In') {
                if ($pass->person_type == 'Tanent' && $pass->user_id) {
                    $flat->tanent_id = $pass->user_id;
                    $flat->living_status = 'Tenant';
                } else {
                    $flat->living_status = 'Owner';
                    $flat->tanent_id = 0;
                }
            } else {
                // Move-Out
                if ($pass->person_type == 'Tanent') {
                    $flat->tanent_id = 0;
                    $flat->living_status = 'Owner'; // Usually reverts to home owner until next tenant
                } else {
                    $flat->living_status = 'Empty'; // Or whatever status means vacant
                }
            }
            $flat->save();
        }

        return response()->json(['msg' => 'Process completed successfully. Pass expired.'], 200);
    }

    // User: Get all move requests for themselves
    public function get_my_move_requests(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $user = Auth::user();
        $sortOrder = $request->input('sort_order', 'desc');

        $requests = MoveInOutRequest::where('user_id', $user->id)
            ->with(['flat.block'])
            ->orderBy('created_at', $sortOrder)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'type' => $r->type,
                    'person_type' => $r->person_type,
                    'status' => $r->status,
                    'passcode' => in_array($r->status, ['Approved']) ? $r->passcode : null,
                    'date_of_entry_exit' => $r->date_of_entry_exit,
                    'flat' => $r->flat ? [
                        'name' => $r->flat->name,
                        'block' => $r->flat->block ? $r->flat->block->name : null,
                    ] : null,
                    'created_at' => $r->created_at,
                ];
            });

        return response()->json(['success' => true, 'requests' => $requests], 200);
    }

    // Accounts: Get move-out requests pending accounts verification
    public function get_accounts_move_out_requests(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'building_id' => 'required|exists:buildings,id',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $sortOrder = $request->input('sort_order', 'desc');

        $requests = MoveInOutRequest::where('building_id', $request->building_id)
            ->where('type', 'Move-Out')
            ->where('status', 'Pending Accounts')
            ->with(['flat.block', 'user'])
            ->orderBy('created_at', $sortOrder)
            ->get();

        return response()->json(['success' => true, 'requests' => $requests], 200);
    }

    // Accounts: Approve or reject move-out request
    public function accounts_approve_move_out(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:move_in_out_requests,id',
            'action' => 'required|in:Approve,Reject',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $moveRequest = MoveInOutRequest::find($request->request_id);

        if ($moveRequest->status !== 'Pending Accounts') {
            return response()->json(['error' => 'Request is not pending accounts verification.'], 422);
        }

        if ($request->action === 'Approve') {
            // Validate pending dues
            $flat = $moveRequest->flat;
            if ($flat && method_exists($flat, 'pendingDues') && $flat->pendingDues() > 0) {
                return response()->json(['error' => 'Cannot approve: flat has pending dues of ' . number_format($flat->pendingDues(), 2)], 422);
            }
            $moveRequest->status = 'Pending';
            $msg = 'Verified by Accounts. Forwarded to BA for final approval.';
        } else {
            $moveRequest->status = 'Rejected';
            $moveRequest->comment = $request->comment ?? 'Rejected by Accounts.';
            $msg = 'Request rejected.';
        }

        $moveRequest->save();
        return response()->json(['msg' => $msg], 200);
    }

    // Get building user limit — to check if "Add User" button should be disabled
    public function get_building_user_limit(Request $request)
    {
        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return response()->json(['error' => 'No building assigned to your account.'], 403);
        }

        // Get 'User' role for this building
        $userRole = Role::where('building_id', $building->id)->where('slug', 'user')->first();

        // Count active users with 'User' role in this building
        $activeCount = BuildingUser::where('building_id', $building->id)
            ->where('role_id', $userRole ? $userRole->id : null)
            ->where('status', 'Active')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->count();

        $limit = (int) $building->no_of_logins;
        $limitReached = $activeCount >= $limit;

        return response()->json([
            'success'       => true,
            'active_count'  => $activeCount,
            'limit'         => $limit,
            'limit_reached' => $limitReached,
            'can_add_user'  => !$limitReached,
        ], 200);
    }

    // Add existing user or create a new user and add to building
    public function add_user_to_building(Request $request)
    {
        $authUser = Auth::user();
        $flat     = AuthHelper::flat();
        $building = $flat ? $flat->building : $authUser->building;

        if (!$building) {
            return response()->json(['error' => 'No building assigned to your account.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id'    => 'nullable|exists:users,id',
            'email'      => 'required_without:user_id|email',
            'first_name' => 'required_without:user_id|string',
            'last_name'  => 'required_without:user_id|string',
            'phone'      => 'nullable|string',
            'gender'     => 'nullable|in:Male,Female,Other',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $isExisting = false;
        $tenant = null;

        if ($request->filled('user_id')) {
            $tenant = User::find($request->user_id);
            $isExisting = true;
        } else {
            // Check if user already exists by email
            $tenant = User::where('email', $request->email)->first();
            if ($tenant) {
                $isExisting = true;
            } else {
                // Check phone uniqueness for new users
                if ($request->filled('phone') && User::where('phone', $request->phone)->exists()) {
                    return response()->json(['error' => 'The phone number is already taken.'], 422);
                }

                // Create new User Account
                $tenant = new User();
                $tenant->first_name = $request->first_name;
                $tenant->last_name = $request->last_name;
                $tenant->email = $request->email;
                $tenant->phone = $request->phone;
                $tenant->gender = $request->gender;
                $rawPassword = 'MFI@' . rand(1000, 9999);
                $tenant->password = Hash::make($rawPassword);
                $tenant->role = 'user';
                $tenant->status = 'Active';
                $tenant->building_id = $building->id;
                $tenant->created_by = $authUser->id; // The owner ID
                $tenant->created_type = 'direct';
                
                // If a flat is selected, assign it
                if ($flat) {
                    $tenant->flat_id = $flat->id;
                }
                
                $tenant->save();

                // Send Email with password
                try {
                    $setting = \App\Models\Setting::first();
                    $logo = $setting ? $setting->logo : null;
                    $info = array(
                        'user' => $tenant,
                        'password' => $rawPassword,
                        'logo' => $logo,
                    );
                    \Illuminate\Support\Facades\Mail::send('email.password', $info, function ($message) use ($tenant) {
                        $message->to($tenant->email, $tenant->first_name . ' ' . $tenant->last_name)
                                ->subject('Your MyFlatInfo Account Has Been Created');
                    });
                } catch (\Exception $e) {
                    \Log::error('Failed to send password email: ' . $e->getMessage());
                }
            }
        }

        // Get or create building-specific 'user' role
        $userRole = Role::where('building_id', $building->id)->where('slug', 'user')->first();

        if (!$userRole) {
            $userRole = new Role();
            $userRole->building_id = $building->id;
            $userRole->name = 'User';
            $userRole->slug = 'user';
            $userRole->type = 'user';
            $userRole->save();
        }

        // Check login limit
        $activeCount = BuildingUser::where('building_id', $building->id)
            ->where('role_id', $userRole->id)
            ->where('status', 'Active')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->count();

        $limit = (int) $building->no_of_logins;

        // Check if already assigned with User role
        $alreadyExists = BuildingUser::where('building_id', $building->id)
            ->where('user_id', $tenant->id)
            ->where('role_id', $userRole->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($alreadyExists) {
            // Update flat_id if flat is selected and they don't have one
            if ($flat && !$tenant->flat_id) {
                $tenant->flat_id = $flat->id;
                $tenant->save();
            }
            return response()->json(['error' => 'User is already assigned to this building.'], 422);
        }

        if ($activeCount >= $limit) {
            return response()->json([
                'error'        => 'User limit reached. Cannot add more users.',
                'active_count' => $activeCount,
                'limit'        => $limit,
            ], 422);
        }

        // Add user to building
        $buildingUser = new BuildingUser();
        $buildingUser->building_id = $building->id;
        $buildingUser->user_id     = $tenant->id;
        $buildingUser->role_id     = $userRole->id;
        $buildingUser->status      = 'Active';
        $buildingUser->save();
        
        // Ensure flat_id is set for existing user if not already set
        if ($isExisting && $flat && !$tenant->flat_id) {
            $tenant->flat_id = $flat->id;
            $tenant->save();
        }

        return response()->json([
            'success'      => true,
            'is_existing'  => $isExisting,
            'msg'          => $isExisting ? 'Existing user added to building successfully.' : 'User created and added successfully.',
            'active_count' => $activeCount + 1,
            'limit'        => $limit,
            'user'         => [
                'id'         => $tenant->id,
                'first_name' => $tenant->first_name,
                'last_name'  => $tenant->last_name,
                'email'      => $tenant->email,
                'phone'      => $tenant->phone,
            ]
        ], 201);
    }

    // Owner: Get tenants and users associated with my flats in a single API
    public function get_my_tenant_and_user_list(Request $request)
    {
        $owner    = Auth::user();
        $flat     = AuthHelper::flat();
        $building = $flat ? $flat->building : $owner->building;

        if (!$building) {
            return response()->json(['error' => 'No building selected or assigned to your account.'], 403);
        }

        // 1. Get all flats owned by this user
        $myFlats = Flat::where('building_id', $building->id)
            ->where('owner_id', $owner->id)
            ->with(['tanent', 'block'])
            ->get();

        // 2. Fetch Tenants
        $tenants = $myFlats->map(function ($flat) use ($owner) {
            if (!$flat->tanent) return null;

            return [
                'tenant' => [
                    'id'         => $flat->tanent->id,
                    'first_name' => $flat->tanent->first_name,
                    'last_name'  => $flat->tanent->last_name,
                    'gender'     => $flat->tanent->gender,
                    'email'      => $flat->tanent->email,
                    'phone'      => $flat->tanent->phone,
                    'status'     => $flat->tanent->status,
                ],
                'flat' => [
                    'id'    => $flat->id,
                    'name'  => $flat->name,
                    'block' => $flat->block ? $flat->block->name : null,
                ],
                'added_by' => [
                    'id'         => $owner->id,
                    'first_name' => $owner->first_name,
                    'last_name'  => $owner->last_name,
                ],
            ];
        })->filter()->values();

        // 3. Fetch Building Users associated with my flats
        $ownerFlatIds = $myFlats->pluck('id');

        $userRole = Role::where('building_id', $building->id)
            ->where('slug', 'user')
            ->first();

        $limit       = (int) $building->no_of_logins;
        $activeCount = BuildingUser::where('building_id', $building->id)
            ->where('role_id', $userRole ? $userRole->id : null)
            ->where('status', 'Active')
            ->count();

        $buildingUsers = BuildingUser::where('building_id', $building->id)
            ->where('role_id', $userRole ? $userRole->id : null)
            ->with('user')
            ->get();

        $users = $buildingUsers->map(function ($bu) use ($myFlats, $owner) {
            if (!$bu->user) return null;

            // Find which flat owned by this owner is associated with this user (either as main tenant or via user's flat_id)
            $linkedFlat = $myFlats->first(function ($flat) use ($bu) {
                return $flat->tanent_id == $bu->user_id || $bu->user->flat_id == $flat->id;
            });

            // If not linked to any flat, but created by this owner, we still want to show them
            // so the active_count matches the items in the list.
            if (!$linkedFlat && $bu->user->created_by != $owner->id) {
                return null;
            }

            // Fetch details of the tenant/user who created this user
            $creator = null;
            if ($bu->user && $bu->user->created_by) {
                $creatorUser = \App\Models\User::find($bu->user->created_by);
                if ($creatorUser) {
                    $creator = [
                        'id'         => $creatorUser->id,
                        'first_name' => $creatorUser->first_name,
                        'last_name'  => $creatorUser->last_name,
                        'email'      => $creatorUser->email,
                        'phone'      => $creatorUser->phone,
                    ];
                }
            }

            return [
                'building_user_id' => $bu->id,
                'status'           => $bu->status,
                'user' => [
                    'id'         => $bu->user->id,
                    'first_name' => $bu->user->first_name,
                    'last_name'  => $bu->user->last_name,
                    'gender'     => $bu->user->gender,
                    'email'      => $bu->user->email,
                    'phone'      => $bu->user->phone,
                ],
                'flat' => $linkedFlat ? [
                    'id'    => $linkedFlat->id,
                    'name'  => $linkedFlat->name,
                    'block' => $linkedFlat->block ? $linkedFlat->block->name : null,
                ] : null,
                'tenant' => ($linkedFlat && $linkedFlat->tanent) ? [
                    'id'         => $linkedFlat->tanent->id,
                    'first_name' => $linkedFlat->tanent->first_name,
                    'last_name'  => $linkedFlat->tanent->last_name,
                    'email'      => $linkedFlat->tanent->email,
                    'phone'      => $linkedFlat->tanent->phone,
                ] : null,
                'created_by_tenant' => $creator,
            ];
        })->filter()->values();

        return response()->json([
            'success'       => true,
            'active_count'  => $activeCount,
            'limit'         => $limit,
            'limit_reached' => $activeCount >= $limit,
            'tenants'       => $tenants,
            'users'         => $users,
        ], 200);
    }
}
