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

        return view('admin.move_in_out.index', compact('building', 'requests'));
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
            'type' => 'required|in:Move-In,Move-Out',
            'person_type' => 'required|in:Owner,Tanent',
            'email' => 'required|email',
            'phone' => 'required',
            'date_of_entry_exit' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $flat = Flat::find($request->flat_id);
        
        // Find user by email or phone
        $user = User::where('email', $request->email)->orWhere('phone', $request->phone)->first();
        
        $moveRequest = new MoveInOutRequest($request->all());
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
        
        // Final Approval by BA
        if ($moveRequest->status == 'Pending' && ($user->role == 'BA' || $user->hasRole('president'))) {
            $moveRequest->status = 'Approved';
            $moveRequest->approved_by = Auth::id();
            $moveRequest->passcode = MoveInOutRequest::generatePasscode();
            $moveRequest->save();
            $this->sendNotification($moveRequest);
            return redirect()->back()->with('success', 'Request approved and passcode generated.');
        } 
        
        // Accounts verification
        if ($moveRequest->status == 'Pending Accounts' && $user->hasRole('accounts')) {
            $moveRequest->status = 'Pending'; // Send back to BA
            $moveRequest->save();
            return redirect()->back()->with('success', 'Verified by Accounts. Now pending with BA for final approval.');
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
        $moveRequest->comment = $request->comment;
        $moveRequest->save();

        return redirect()->back()->with('success', 'Request rejected.');
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
                'type' => 'MOVE_PASS',
                'passcode' => $moveRequest->passcode,
                'request_id' => $moveRequest->id
            ]);
        }
    }
}
