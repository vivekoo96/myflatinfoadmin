<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MoveInOutRequest;
use App\Models\Flat;
use App\Models\User;
use App\Models\Building;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use Carbon\Carbon;

class MoveInOutController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::User();
        if ($user->role != 'BA' && !$user->hasRole('president') && !$user->hasRole('accounts')) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $user->building;
        $query = MoveInOutRequest::where('building_id', $building->id);

        if ($user->hasRole('accounts')) {
            $query->where('type', 'Move-Out');
        }

        $requests = $query->with(['flat', 'user', 'approver'])
            ->orderByDesc('created_at')
            ->get();

        $setting = \App\Models\Setting::first();
        return view('admin.move_in_out.index', compact('building', 'requests', 'setting'));
    }

    public function create()
    {
        if (Auth::User()->role != 'BA') {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::User()->building;
        $flats = Flat::where('building_id', $building->id)->orderBy('name')->get();
        return view('admin.move_in_out.create', compact('building', 'flats'));
    }

    public function store(Request $request)
    {
        if (Auth::User()->role != 'BA') {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $validator = Validator::make($request->all(), [
            'flat_id' => 'required|exists:flats,id',
            'type' => 'nullable|in:Move-In,Move-Out',
            'person_type' => 'required|in:Owner,Tanent',
            'email' => 'required|email',
            'phone' => 'required',
            'date_of_entry_exit' => 'required|date',
            'first_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $flat = Flat::find($request->flat_id);
        
        // Find user by email or phone
        $user = User::where('email', $request->email)->orWhere('phone', $request->phone)->first();
        
        $moveRequest = new MoveInOutRequest($request->all());
        $moveRequest->type = $request->type ?? 'Move-In'; // Default to Move-In
        if ($user) {
            $moveRequest->first_name = $user->first_name ?? $user->name;
            $moveRequest->last_name = $user->last_name ?? '';
        } else {
            $moveRequest->first_name = $request->first_name ?? 'Guest';
            $moveRequest->last_name = $request->last_name ?? '';
        }
        $moveRequest->building_id = Auth::User()->building_id;
        $moveRequest->user_id = $user ? $user->id : null;
        $moveRequest->status = 'Approved'; // BA created are pre-approved
        $moveRequest->approved_by = Auth::id();
        $moveRequest->passcode = MoveInOutRequest::generatePasscode();
        $moveRequest->save();

        // Send email notification
        $this->sendNotification($moveRequest);

        return redirect()->route('move-in-out.index')->with('success', 'Move-In/Out pass created successfully. Passcode: ' . $moveRequest->passcode);
    }

    public function approve($id)
    {
        $user = Auth::User();
        if ($user->role != 'BA' && !$user->hasRole('president') && !$user->hasRole('accounts')) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $moveRequest = MoveInOutRequest::where('id', $id)->where('building_id', Auth::User()->building_id)->firstOrFail();
        
        // Accounts verification
        if ($moveRequest->status == 'Pending Accounts' && $user->hasRole('accounts')) {
            // Validate pending dues
            $flat = $moveRequest->flat;
            if ($flat && method_exists($flat, 'pendingDues') && $flat->pendingDues() > 0) {
                return redirect()->back()->with('error', 'Cannot approve: Flat has pending dues of ' . number_format($flat->pendingDues(), 2));
            }

            $moveRequest->status = 'Pending'; // Send back to BA
            $moveRequest->save();
            return redirect()->back()->with('success', 'Verified by Accounts. Now pending with BA for final approval.');
        }

        // Final Approval by BA
        if ($moveRequest->status == 'Pending' && ($user->role == 'BA' || $user->hasRole('president'))) {
            // Check dues on move-out requests at BA final approval step too
            if ($moveRequest->type == 'Move-Out') {
                $flat = $moveRequest->flat;
                if ($flat && method_exists($flat, 'pendingDues') && $flat->pendingDues() > 0) {
                    return redirect()->back()->with('error', 'Cannot approve: Flat has pending dues of ' . number_format($flat->pendingDues(), 2));
                }
            }

            $moveRequest->status = 'Approved';
            $moveRequest->approved_by = Auth::id();
            $moveRequest->passcode = MoveInOutRequest::generatePasscode();
            $moveRequest->save();
            $this->sendNotification($moveRequest);
            return redirect()->back()->with('success', 'Request approved and passcode generated.');
        } 

        return redirect()->back()->with('error', 'Invalid action for current status or role.');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::User();
        if ($user->role != 'BA' && !$user->hasRole('president') && !$user->hasRole('accounts')) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $moveRequest = MoveInOutRequest::where('id', $id)->where('building_id', Auth::User()->building_id)->firstOrFail();
        
        $moveRequest->status = 'Rejected';
        $moveRequest->rejected_comment = $request->comment;
        $moveRequest->save();

        return redirect()->back()->with('success', 'Request rejected.');
    }

    public function fetchByContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $contact = $request->contact;
        $user = User::where('email', $contact)->orWhere('phone', $contact)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found in system.'], 404);
        }

        // Find the flat owned or rented by this user in the current building
        $flat = Flat::where('building_id', Auth::User()->building_id)
            ->where(function($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhere('tanent_id', $user->id);
            })
            ->with('block')
            ->first();

        if (!$flat) {
            return response()->json([
                'success' => false, 
                'message' => 'User found, but no active flat assigned in this building.',
                'user' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone
                ]
            ], 404);
        }

        $personType = ($flat->owner_id == $user->id) ? 'Owner' : 'Tanent';

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone
            ],
            'flat' => [
                'id' => $flat->id,
                'name' => $flat->name,
                'block_name' => $flat->block ? $flat->block->name : 'N/A'
            ],
            'person_type' => $personType
        ], 200);
    }

    private function sendNotification($moveRequest)
    {
        $user = $moveRequest->user;
        $email = $user ? $user->email : $moveRequest->email;
        $phone = $user ? $user->phone : $moveRequest->phone;
        $name = $user ? $user->name : ($moveRequest->first_name . ' ' . $moveRequest->last_name);

        if ($email) {
            try {
                Mail::send('emails.move_pass', ['request' => $moveRequest, 'name' => $name], function ($m) use ($email, $name) {
                    $m->to($email, $name)->subject('Move-In/Out Pass Generated');
                });
            } catch (\Exception $e) {
                \Log::error('Email failed for move pass: ' . $e->getMessage());
            }
        }

        if ($user) {
            $title = 'Move-In/Out Pass generated';
            $body = "Your {$moveRequest->type} pass for flat {$moveRequest->flat->name} has been generated. Passcode: {$moveRequest->passcode}";
            NotificationHelper::sendNotification($user->id, $title, $body, [
                'type' => 'MOVE_PASS_APPROVED',
                'categoryId' => 'PlannedVisitors',
                'channelId' => 'longring',
                'sound' => 'bellnotificationsound.wav',
                'screen' => 'SM_ViewPasseDetailsPage',
                'params' => json_encode([
                    'screenTab' => '',
                    'request_id' => (string) $moveRequest->id,
                    'building_id' => (string) $moveRequest->building_id,
                    'flat_id' => (string) $moveRequest->flat_id,
                    'user_id' => (string) ($moveRequest->user_id ?? ''),
                ]),
                'passcode' => $moveRequest->passcode,
                'request_id' => $moveRequest->id
            ]);
        }

        // Notify flat owner if passcode is generated for a Tenant
        $flat = $moveRequest->flat;
        if ($moveRequest->person_type == 'Tanent' && $flat && $flat->owner_id) {
            $owner = $flat->owner;
            if ($owner) {
                $ownerEmail = $owner->email;
                $ownerName = $owner->name;
                
                if ($ownerEmail) {
                    try {
                        Mail::send('emails.move_pass_owner_notification', [
                            'request' => $moveRequest,
                            'owner' => $owner,
                            'tenant_name' => $name
                        ], function ($m) use ($ownerEmail, $ownerName) {
                            $m->to($ownerEmail, $ownerName)->subject('Tenant Move-In/Out Pass Generated');
                        });
                    } catch (\Exception $e) {
                        \Log::error('Email failed to owner for move pass: ' . $e->getMessage());
                    }
                }
                
                $ownerTitle = 'Tenant Move-In/Out Pass generated';
                $ownerBody = "A {$moveRequest->type} pass for your flat {$flat->name} has been generated for tenant {$name}. Passcode: {$moveRequest->passcode}";
                NotificationHelper::sendNotification($owner->id, $ownerTitle, $ownerBody, [
                    'type' => 'MOVE_PASS_APPROVED',
                    'categoryId' => 'PlannedVisitors',
                    'channelId' => 'longring',
                    'sound' => 'bellnotificationsound.wav',
                    'screen' => 'SM_ViewPasseDetailsPage',
                    'params' => json_encode([
                        'screenTab' => '',
                        'request_id' => (string) $moveRequest->id,
                        'building_id' => (string) $moveRequest->building_id,
                        'flat_id' => (string) $moveRequest->flat_id,
                        'user_id' => (string) ($moveRequest->user_id ?? ''),
                    ]),
                    'passcode' => $moveRequest->passcode,
                    'request_id' => $moveRequest->id
                ]);
            }
        }
    }
}
