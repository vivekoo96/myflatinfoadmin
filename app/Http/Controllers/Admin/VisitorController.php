<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Facility;
use App\Models\Building;
use App\Models\Visitor;
use App\Models\Flat;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class VisitorController extends Controller
{

    public function index()
    {
        
         if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" ||  Auth::user()->selectedRole->name == "Security" || Auth::User()->hasPermission('custom.visitors') )
        
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $user = Auth::User();
        $building = Auth::User()->building;
        $visitors = Visitor::withTrashed()
            ->where('building_id', Auth::User()->building_id)
            ->with(['flat', 'block', 'user'])
            ->orderByDesc('id')
            ->get();
        
        return view('admin.visitor.index', compact('building', 'visitors'));
    }


    // Show invite guest form for BA (redirect to unified view)
    public function inviteGuestForm()
    {
        if (!Auth::User()->role == 'BA') {
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        // Redirect to unified visitor management
        return redirect()->route('visitor.index')->with('info', 'Use the "Invite Guest" button to create invitations.');
    }

    // Handle invite guest submission
    public function inviteGuest(Request $request)
    {
        if (!Auth::User()->role == 'BA') {
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'name' => 'required|string|min:2|max:100',
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'vehicle_number' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:30',
            'pic' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'date' => 'required|date',
            'time' => 'required',
            'guests_count' => 'required|numeric|min:1|max:20',
        ];
        $messages = [
            'name.required' => 'Guest name is required.',
            'name.min' => 'Guest name must be at least 2 characters.',
            'name.max' => 'Guest name must not exceed 100 characters.',
            'mobile.required' => 'Mobile number is required.',
            'mobile.regex' => 'Please enter a valid mobile number.',
            'vehicle_number.max' => 'Vehicle number must not exceed 20 characters.',
            'vehicle_type.max' => 'Vehicle type must not exceed 30 characters.',
            'pic.image' => 'Picture must be an image.',
            'pic.mimes' => 'Picture must be jpeg, jpg, or png.',
            'pic.max' => 'Picture must not exceed 2MB.',
            'date.required' => 'Date is required.',
            'date.date' => 'Date must be valid.',
            'time.required' => 'Time is required.',
            'guests_count.required' => 'Number of guests is required.',
            'guests_count.numeric' => 'Number of guests must be a number.',
            'guests_count.min' => 'At least 1 guest is required.',
            'guests_count.max' => 'Maximum 20 guests allowed.',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        $visitor = new Visitor();
        $visitor->user_id = Auth::User()->id;
        $visitor->building_id = Auth::User()->building_id;
        $visitor->name = $request->name;
        $visitor->head_name = $request->name;
        $visitor->head_phone = $request->mobile;
        $visitor->vehicle_number = $request->vehicle_number;
        $visitor->vehicle_type = $request->vehicle_type;
        $visitor->stay_from = $request->date;
        $visitor->stay_to = $request->date;
        $visitor->total_members = $request->guests_count;
        $visitor->type = 'Planned';
        $visitor->status = 'Invited';
        $visitor->visiting_purpose = 'Guest Invitation';
        if ($request->hasFile('pic')) {
            $file = $request->file('pic');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();

            // Delete old image if exists
            if (!empty($visitor->head_photo)) {
                $file_path = public_path($visitor->head_photo);
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'visitors/' . uniqid() . '.' . $extension;
            $file->move(public_path('/images/visitors/'), $filename);
            $visitor->head_photo = $filename;
        }
        // Generate unique 8-character passcode (letters & numbers)
        do {
            $passcode = Str::upper(Str::random(8));
        } while (Visitor::where('code', $passcode)->exists());
        $visitor->code = $passcode;
        $visitor->save();
        // Here you can add logic to send SMS/email invitation to guest with passcode
        return redirect()->back()->with('success', 'Invitation sent! Passcode: ' . $passcode);
    }

    public function store(Request $request)
    {
       
        $user = Auth::User();
        if($user->hasRole('security') || Auth::User()->role == 'BA')
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        
        // Handle date/time combination
        if ($request->visit_date && $request->visit_time) {
            $request->merge([
                'stay_from' => $request->visit_date . ' ' . $request->visit_time,
                'stay_to' => $request->visit_date . ' ' . $request->visit_time
            ]);
        }
        
        $rules = [
            'block_id' => 'required|exists:blocks,id',
            'flat_id' => 'required|exists:flats,id',
            'type' => ['required', Rule::in(['Planned', 'Unplanned'])],
            'total_members' => 'required|numeric|min:1',
            'head_name' => 'required|string|min:2|max:100',
            'head_phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'head_photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'stay_from' => 'required|date',
            'stay_to' => 'required|date|after_or_equal:stay_from',
            'visiting_purpose' => 'required|string|max:255',
        ];

        $messages = [
            'block_id.required' => 'Block is required.',
            'block_id.exists' => 'Selected block does not exist.',
            'flat_id.required' => 'Flat is required.',
            'flat_id.exists' => 'Selected flat does not exist.',
            'type.required' => 'Visit type is required.',
            'type.in' => 'Visit type must be Planned or Unplanned.',
            'total_members.required' => 'Total members is required.',
            'total_members.numeric' => 'Total members must be a number.',
            'total_members.min' => 'Total members must be at least 1.',
            'head_name.required' => 'Head name is required.',
            'head_name.string' => 'Head name must be a string.',
            'head_name.min' => 'Head name must be at least 2 characters.',
            'head_name.max' => 'Head name must not exceed 100 characters.',
            'head_phone.required' => 'Head phone is required.',
            'head_phone.regex' => 'Please enter a valid phone number (10 digits starting with 6, 7, 8, or 9).',
            'head_photo.image' => 'Head photo must be an image.',
            'head_photo.mimes' => 'Head photo must be a jpeg, jpg, or png file.',
            'head_photo.max' => 'Head photo must not exceed 2MB.',
            'stay_from.required' => 'Stay from date is required.',
            'stay_from.date' => 'Stay from must be a valid date.',
            'stay_to.required' => 'Stay to date is required.',
            'stay_to.date' => 'Stay to must be a valid date.',
            'stay_to.after_or_equal' => 'Stay to date must be after or equal to Stay from date.',
            'visiting_purpose.required' => 'Visiting purpose is required.',
            'visiting_purpose.string' => 'Visiting purpose must be a string.',
            'visiting_purpose.min' => 'Visiting purpose must be at least 5 characters.',
            'visiting_purpose.max' => 'Visiting purpose must not exceed 255 characters.',
        ];
    
        $msg = 'Visitor added successfully';
        $visitor = new Visitor();
    
        if ($request->id) {
            $visitor = Visitor::withTrashed()->find($request->id);
            $msg = 'Visitor Updated';
        }
    
        $validation = \Validator::make($request->all(), $rules, $messages);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        if($request->hasFile('head_photo')) {
            $file = $request->file('head_photo');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            
            // Delete old image if exists
            if (!empty($visitor->head_photo)) {
                $file_path = public_path($visitor->head_photo);
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }
            
            $filename = 'visitors/' . uniqid() . '.' . $extension;
            $file->move(public_path('/images/visitors/'), $filename);
            $visitor->head_photo = $filename;
        }
        do {
            $code = Str::random(10);
        } while (\App\Models\GatePass::where('code', $code)->exists());
        $flat = Flat::find($request->flat_id);
        if($flat->tanent_id > 0){
            $visitor->user_id = $flat->tanent_id;
        }elseif($flat->owner_id > 0){
            $visitor->user_id = $flat->owner_id;
        }else{
            // Fallback: Use current authenticated user if no tenant or owner is assigned
            $visitor->user_id = Auth::User()->id;
        }
        $visitor->building_id = Auth::User()->building_id;
        $visitor->block_id = $request->block_id;
        $visitor->flat_id = $request->flat_id;
        $visitor->type = $request->type;
        $visitor->total_members = $request->total_members;
        $visitor->head_name = $request->head_name;
        $visitor->head_phone = $request->head_phone;
        $visitor->stay_from = $request->stay_from;
        $visitor->stay_to = $request->stay_to;
        $visitor->visiting_purpose = $request->visiting_purpose;
        $visitor->status = $request->status;
        $visitor->code = $code;
        $visitor->save();
    
        return redirect()->back()->with('success', $msg);
    }

    public function update(Request $request, $id)
    {
        // Redirect to store method since it handles both create and update
        $request->merge(['id' => $id]);
        return $this->store($request);
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.visitors') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $user = Auth::User();
        $visitor = Visitor::where('id',$id)->where('building_id',Auth::User()->building_id)->withTrashed()->first();
        if(!$visitor){
            return redirect()->route('visitor.index');
        }
        return view('admin.visitor.show',compact('visitor'));
    }
    
    public function edit($id)
    {
        //
    }

    public function destroy($id, Request $request)
    {
        if(Auth::User()->hasRole('security') )
        {
            //
        }else{
            return response()->json(['error' => 'Permission denied!'], 403);
        }
        
        // Use the ID from request if provided, otherwise use route parameter
        $visitorId = $request->id ?? $id;
        $visitor = Visitor::where('id', $visitorId)->withTrashed()->first();
        
        if (!$visitor) {
            return response()->json(['error' => 'Visitor not found'], 404);
        }
        
        if($request->action == 'delete'){
            $visitor->delete();
            $message = 'Visitor deleted successfully';
        }else{
            $visitor->restore();
            $message = 'Visitor restored successfully';
        }
        
        return response()->json([
            'msg' => 'success',
            'message' => $message
        ], 200);
    }


        // List all invitations (redirect to unified view)
    public function invitationsList()
    {
        if (!Auth::User()->role == 'BA') {
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        // Redirect to unified visitor management
        return redirect()->route('visitor.index')->with('info', 'All visitors and invitations are now managed in one place.');
    }

    // Add or Edit invitation (from modal)
    public function saveInvitation(Request $request)
    {
        if (Auth::User()->role != 'BA') {
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        
        // Handle date/time combination for invitations
        if ($request->visit_date && $request->visit_time) {
            $request->merge([
                'date' => $request->visit_date,
                'time' => $request->visit_time
            ]);
        }
        
        $rules = [
            'head_name' => 'required|string|min:2|max:100',
            'head_phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'vehicle_number' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:30',
            'head_photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'visit_date' => 'required|date|after_or_equal:today',
            'visit_time' => 'required',
            'total_members' => 'required|numeric|min:1|max:20',
            'invitation_email' => 'nullable|email|max:100',
        ];
        
        $messages = [
            'head_name.required' => 'Guest name is required.',
            'head_name.min' => 'Guest name must be at least 2 characters.',
            'head_name.max' => 'Guest name must not exceed 100 characters.',
            'head_phone.required' => 'Mobile number is required.',
            'head_phone.regex' => 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.',
            'vehicle_number.max' => 'Vehicle number must not exceed 20 characters.',
            'vehicle_type.max' => 'Vehicle type must not exceed 30 characters.',
            'head_photo.image' => 'Picture must be an image file.',
            'head_photo.mimes' => 'Picture must be jpeg, jpg, or png format.',
            'head_photo.max' => 'Picture size must not exceed 2MB.',
            'visit_date.required' => 'Visit date is required.',
            'visit_date.date' => 'Please enter a valid date.',
            'visit_date.after_or_equal' => 'Visit date cannot be in the past.',
            'visit_time.required' => 'Visit time is required.',
            'total_members.required' => 'Number of guests is required.',
            'total_members.numeric' => 'Number of guests must be a valid number.',
            'total_members.min' => 'At least 1 guest is required.',
            'total_members.max' => 'Maximum 20 guests allowed.',
            'invitation_email.email' => 'Please enter a valid email address.',
            'invitation_email.max' => 'Email address must not exceed 100 characters.',
        ];
        
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        
        // Check if editing existing invitation
        if ($request->id) {
            $visitor = Visitor::withTrashed()->find($request->id);
            if (!$visitor) {
                return redirect()->back()->with('error', 'Invitation not found.');
            }
            $message = 'Invitation updated successfully!';
        } else {
            $visitor = new Visitor();
            // Generate unique 8-character alphanumeric passcode
            do {
                $passcode = strtoupper(Str::random(4) . rand(1000, 9999));
                $passcode = substr($passcode, 0, 8);
            } while (Visitor::where('code', $passcode)->exists());
            $visitor->user_id = Auth::User()->id;
            $visitor->block_id = $request->block_id;
            $visitor->flat_id = $request->flat_id;
            $visitor->code = $passcode;
            $visitor->type = 'Planned';
            $visitor->status = 'Invited';
            $visitor->building_id = Auth::User()->building_id;
            $message = 'Invitation created successfully! Passcode: ' . $passcode;
        }
        
        // Handle photo upload
        if ($request->hasFile('head_photo')) {
            $file = $request->file('head_photo');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();

            // Delete old photo if updating
            if (!empty($visitor->head_photo)) {
                $file_path = public_path($visitor->head_photo);
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'visitors/' . uniqid() . '.' . $extension;
            $file->move(public_path('/images/visitors/'), $filename);
            $visitor->head_photo = $filename;
        }
        
        // Set visitor data
        $visitor->head_name = $request->head_name;
        $visitor->head_phone = $request->head_phone;
        $visitor->vehicle_number = $request->vehicle_number;
        $visitor->vehicle_type = $request->vehicle_type;
        $visitor->stay_from = $request->visit_date . ' ' . $request->visit_time;
        $visitor->stay_to = $request->visit_date . ' ' . $request->visit_time;
        $visitor->total_members = $request->total_members;
        $visitor->visiting_purpose = 'Guest Invitation';
        $visitor->invitation_email = $request->invitation_email;
        $visitor->invitation_sent_at = now();
        
        try {
            \Log::info('Attempting to save visitor invitation', [
                'visitor_data' => $visitor->toArray(),
                'request_data' => $request->all()
            ]);
            
            $visitor->save();
            
            \Log::info('Visitor saved successfully with ID: ' . $visitor->id);
            
            // Send email invitation if email is provided
            $emailSent = false;
            if ($visitor->invitation_email) {
                $emailSent = $this->sendInvitationEmail($visitor);
            }
            
            // Update success message based on email status
            if ($emailSent) {
                $message .= ' Email invitation sent to ' . $visitor->invitation_email;
            } elseif ($visitor->invitation_email) {
                $message .= ' (Email sending failed - check logs)';
            }
            
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Failed to save visitor invitation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return redirect()->back()->with('error', 'Failed to save invitation: ' . $e->getMessage());
        }
    }

    // Send invitation email to guest
    private function sendInvitationEmail($visitor)
    {
        try {
            $building = Auth::User()->building;
            $emailData = [
                'guest_name' => $visitor->head_name,
                'building_name' => $building->name,
                'building_address' => $building->address,
                'visit_date' => date('d-m-Y', strtotime($visitor->stay_from)),
                'visit_time' => date('H:i', strtotime($visitor->stay_from)),
                'total_guests' => $visitor->total_members,
                'passcode' => $visitor->code,
                'vehicle_details' => $visitor->vehicle_number ? $visitor->vehicle_number . ' (' . $visitor->vehicle_type . ')' : 'Not provided',
                'contact_number' => $visitor->head_phone,
                'ba_name' => Auth::User()->name,
                'ba_contact' => Auth::User()->phone ?? 'Not provided'
            ];

            Mail::send('emails.guest_invitation', $emailData, function ($message) use ($visitor, $building) {
                $message->to($visitor->invitation_email, $visitor->head_name)
                        ->subject('Guest Invitation - ' . $building->name)
                        ->from(config('mail.from.address'), $building->name);
            });

            // Update invitation sent status
            $visitor->update(['invitation_sent_at' => now()]);
            
            \Log::info('Invitation email sent successfully to: ' . $visitor->invitation_email);
            
            return true; // Email sent successfully
            
        } catch (\Exception $e) {
            \Log::error('Failed to send invitation email: ' . $e->getMessage());
            return false; // Email sending failed
        }
    }

    // Soft delete invitation
    public function softDeleteInvitation($id)
    {
        if (!Auth::User()->role == 'BA') {
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $visitor = Visitor::find($id);
        if (!$visitor) return redirect()->back()->with('error', 'Invitation not found.');
        $visitor->delete();
        return redirect()->back()->with('success', 'Invitation deleted (soft).');
    }

    // Restore soft-deleted invitation
    public function restoreInvitation($id)
    {
        if (!Auth::User()->role == 'BA') {
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $visitor = Visitor::withTrashed()->find($id);
        if (!$visitor) return redirect()->back()->with('error', 'Invitation not found.');
        $visitor->restore();
        return redirect()->back()->with('success', 'Invitation restored.');
    }

    // User History Page
    public function userHistory($phone)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.visitors') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        $visits = Visitor::where('head_phone', $phone)
                        ->where('building_id', Auth::User()->building_id)
                        ->with(['flat.block'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(25);
        
        $userInfo = Visitor::where('head_phone', $phone)
                          ->where('building_id', Auth::User()->building_id)
                          ->first();
        
        $totalVisits = Visitor::where('head_phone', $phone)
                             ->where('building_id', Auth::User()->building_id)
                             ->count();
        
        $completedVisits = Visitor::where('head_phone', $phone)
                                 ->where('building_id', Auth::User()->building_id)
                                 ->whereNotNull('stay_from')
                                 ->whereNotNull('stay_to')
                                 ->where('stay_from', '!=', 'stay_to')
                                 ->count();
        
        $ongoingVisits = Visitor::where('head_phone', $phone)
                               ->where('building_id', Auth::User()->building_id)
                               ->whereNotNull('stay_from')
                               ->where(function($query) {
                                   $query->whereNull('stay_to')
                                         ->orWhere('stay_from', '=', 'stay_to');
                               })
                               ->count();
        
        $lastVisit = Visitor::where('head_phone', $phone)
                           ->where('building_id', Auth::User()->building_id)
                           ->latest()
                           ->first();
        
        return view('admin.visitor.user-history', compact(
            'visits', 'userInfo', 'phone', 'totalVisits', 
            'completedVisits', 'ongoingVisits', 'lastVisit'
        ));
    }

    // Get Timeline for a specific visit
    public function getTimeline($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.visitors') )
        {
            //
        }else{
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $visit = Visitor::where('id', $id)
                       ->where('building_id', Auth::User()->building_id)
                       ->with(['flat.block'])
                       ->first();
        
        if (!$visit) {
            return response()->json(['success' => false, 'message' => 'Visit not found'], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $visit
        ]);
    }
}
