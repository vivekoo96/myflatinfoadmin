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

    // Owner creates tanent and move-in request
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
            // 'id_proof' => 'required|file'
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
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $user = Auth::user();
        $buildingId = $request->building_id; // Security should pass their building_id

        $requests = MoveInOutRequest::where('building_id', $buildingId)
            ->where('type', $request->type)
            ->where('status', 'Approved')
            ->whereNull('visited_at')
            ->with(['flat', 'user'])
            ->get();

        return response()->json(['success' => true, 'requests' => $requests], 200);
    }

    // Fetch active passcode for user
    public function get_active_passcode()
    {
        $user = Auth::user();
        $pass = MoveInOutRequest::where('user_id', $user->id)
            ->whereIn('status', ['Approved'])
            ->whereNull('visited_at')
            ->first();

        if ($pass) {
            return response()->json(['success' => true, 'passcode' => $pass->passcode, 'type' => $pass->type], 200);
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
}
