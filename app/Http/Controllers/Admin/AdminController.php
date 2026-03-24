<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Setting;
use App\Models\City;
use App\Models\Building;
use App\Models\BuildingUser;
use App\Models\Transaction;
use App\Models\Order;
use App\Models\Withdraw;
use App\Models\Guard;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use \Hash;
use \Auth;
use \Response;
use \Mail;
use \DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;


class AdminController extends Controller
{
    
    public function index()
    {
        $setting = Setting::first();
        return view('admin.login',compact('setting'));
    }
    public function permission_denied()
    {
        $setting = Setting::first();
        return view('admin.permission_denied',compact('setting'));
    } 
    
    public function save_token(Request $request)
    {
        $user = Auth::User();
        $user->device_token = $request->device_token;
        $user->save();
        return response()->json([
            'msg' => 'success'
        ],200);
    }

    public function login(Request $request)
    {
        // Clear any transient session selections before login
        session()->forget(['selected_role_id', 'current_building_id']);

        $this->validate($request, [
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->orWhere('phone',$request->email)->first();

        if($user){
            // Check if user is BA or has any role other than 'user'
            if($user->role == 'BA'){
                // BA check: if user-level status is Inactive, cannot login
                if($user->status != 'Active'){
                    // dd('HELKlko');
                    return redirect()->back()->with('error', 'Your account is inactive. Please contact support.');
                }
                
                // BA can login
                if(Hash::check($request->password, $user->password)){
                    Auth::login($user, true);
                    $user->device_token = $request->device_token;
                    $user->save();
                    // Ensure current building is set in session for this BA
                    session(['current_building_id' => (int)$user->building_id]);

                    // Try to pick an active BuildingUser assignment for this building
                    $assignment = BuildingUser::where('user_id', $user->id)
                        ->where('building_id', $user->building_id)
                        ->active()
                        ->with('role')
                        ->first();

                    if ($assignment && $assignment->role) {
                        session(['selected_role_id' => $assignment->role_id]);
                        $user->selected_role_id = $assignment->role_id;
                        Auth::setUser($user);
                    }

                    return redirect('/building-option');
                } else {
                    return redirect()->back()->with('error', 'Incorrect password. Please try again.');
                }
            } else {
                // For non-BA users: check if they have at least ONE Active role assignment with type "default"
                $hasActiveDefaultRoleAssignment = BuildingUser::where('user_id', $user->id)
                    ->active()  // Use the active() scope for case-insensitive matching
                    ->whereHas('role', function($query) {
                        $query->where('type', 'default')->orWhere('type', 'custom');
                    })
                    ->exists();
                
                if(!$hasActiveDefaultRoleAssignment){
                    // User has no Active role assignments with type "default"
                    if(Hash::check($request->password, $user->password)){
                        return redirect()->back()->with('error', 'You do not have any active role assignments. Please contact the administrator.');
                    } else {
                        return redirect()->back()->with('error', 'Incorrect password. Please try again.');
                    }
                }
                
                // User has at least one Active assignment with type "default"; check password
                if(Hash::check($request->password, $user->password)){
                    Auth::login($user, true);
                    $user->device_token = $request->device_token;
                    $user->save();
                    return redirect('/building-option');
                } else {
                    return redirect()->back()->with('error', 'Incorrect password. Please try again.');
                }
            }
        }

        return redirect()->back()->with('error', 'Invalid email or password.');

    }

    public function verifyotp(Request $request)
    {
        $this->validate($request, [
            'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'otp' => 'required|digits:4|numeric',
        ]);
        $user = User::where('phone', $request->mobile)->where('role','BA')->first();
        if(!$user){
            return response()->json(['status' => 2, 'error' => 'This number is not registered with us']);
        }
        if(Hash::check($request->otp,$user->otp)){
            Auth::login($user, true);
            $user->device_token = $request->device_token;
            $user->save();
            return response()->json(['status' => 1, 'success' => 'You have Logged in Successfully']);
        }
        return response()->json(['status' => 2, 'error' => 'Invalid Otp']);
    }

    public function building_option()
    {
        $setting = Setting::first();
        $user = Auth::User();
        $buildings = $user->allBuildings();
        return view('admin.building_option', compact('setting', 'buildings'));
    }
    
    public function select_building($building_id)
    {
        $user = Auth::User();
        $building = Building::where('id',$building_id)->first();
        if($building){
            // Do NOT persist the selected building to the user's DB record.
            // Persisting causes changes to affect other active sessions/browsers.
            // Instead, store the selection in session and update the in-memory
            // Auth user for the current session only.
            $user->building_id = (int)$building_id;
            session(['current_building_id' => (int)$building_id]);
            // Update the current in-memory Auth user so subsequent requests
            // in this session use the selected building_id.
            Auth::setUser($user);
            
            // Check if user has multiple roles for this building
            $roles = $user->departments()
                ->where('building_id', $building_id)
                ->with('role')
                ->get()
                ->pluck('role')
                ->filter()
                ->unique('id');
            
            // If user has only one role, select it and go to dashboard
            if($roles->count() == 1){
                // Save selected role to session for current session only
                session()->forget('selected_role_id');
                session(['selected_role_id' => $roles->first()->id]);
                $user->selected_role_id = $roles->first()->id; // update in-memory
                Auth::setUser($user);
                return redirect('/dashboard');
            }
            
            // If user has multiple roles, show role selection
            if($roles->count() > 1){
                return redirect('/select-role')->with('roles', $roles);
            }
        }
        return redirect('/dashboard');
    }
    
   public function role_option()
    {
     $user = Auth::User();
        $building_id = $user->building_id;
        
        // Only include departments/assignments that are Active (excluding issue roles)
        $roles = $user->departments()
            ->where('building_id', $building_id)
            ->active()
            ->with('role')
            ->get()
            ->pluck('role')
            ->filter()
            ->filter(function($role) {
                return $role->type !== 'issue';
            })
            ->unique('id');
           
        
        // Add BA role at the top if user has BA role in users table
        if ($user->role === 'BA') {
            // Create a simple BA role object to add to collection
            $baRole = new \stdClass();
            $baRole->id = 0;
            $baRole->name = 'BA';
            $baRole->description = 'Building Administrator';
            $baRole->type = 'admin';
            
            if (!$roles->contains('id', $baRole->id)) {
                // Add BA role to the beginning of the collection
                $roles = $roles->prepend($baRole);
            }
        }
        // dd($roles);
        
        return view('admin.role_option', compact('roles'));
    }
    
     public function select_role($role_id)
    {
        $user = Auth::User();
        
        // Handle special BA role case
        if ($role_id == 0 && $user->role === 'BA') {
            // Do not persist selected role in DB; keep it in session
            session(['selected_role_id' => $role_id]);
            $user->selected_role_id = $role_id; // update in-memory
            Auth::setUser($user);
            return redirect('/dashboard');
        }
        
        $role = \App\Models\Role::find($role_id);
        
        if($role){
            // Verify user has this role in the current building
            $hasRole = $user->departments()
                ->where('building_id', $user->building_id)
                ->where('role_id', $role_id)
                ->exists();
            
            if($hasRole){
                // Do not persist selected role in DB; keep it in session
                session(['selected_role_id' => $role_id]);
                $user->selected_role_id = $role_id; // update in-memory
                Auth::setUser($user);
            }
        }
        return redirect('/dashboard');
    }
    
    // public function select_role($role_id)
    // {
    //     $user = Auth::User();
    //     $role = \App\Models\Role::find($role_id);
    //     // dd($user);
    //     if($role){
    //         // Verify user has this role in the current building
    //         $hasRole = $user->departments()
    //             ->where('building_id', $user->building_id)
    //             ->where('role_id', $role_id)
    //             ->exists();
            
    //         if($hasRole){
    //             // Do not persist selected role in DB; keep it in session
    //             session(['selected_role_id' => $role_id]);
    //             $user->selected_role_id = $role_id; // update in-memory
    //             Auth::setUser($user);
    //         }
    //     }
    //     return redirect('/dashboard');
    // }
    
    public function dashboard()
    {
         
       $building = \App\Models\Building::withTrashed()
    ->find(Auth::user()->building_id);
        //  dd($building_id);
        // dd($building);
        if($building && $building->status != 'Active'){
            return redirect('/building-option')->with('error','The selected Building is Inactive, Please select any others');
        }
      $building_id = Auth::user()->building_id;
        //
    
        // Get all transactions grouped by date, type, and payment_type
        $transactions = DB::table('transactions')
            ->select(
                DB::raw("DATE(`date`) as day"),
                'type',
                'payment_type',
                DB::raw("SUM(amount) as total")
            )
            ->where('building_id', $building_id)
            ->where('status', 'Success')
            ->groupBy('day', 'type', 'payment_type')
            ->orderBy('day', 'asc')
            ->get();
    
        // Build labels and datasets for transactions
        $days = $transactions->pluck('day')->unique()->values();
    
        $data = [
            'Credit-InBank' => [],
            'Credit-InHand' => [],
            'Debit-InBank' => [],
            'Debit-InHand' => [],
        ];
    
        foreach ($days as $day) {
            foreach ($data as $key => $val) {
                $data[$key][] = 0; // Initialize all to 0
            }
    
            foreach ($transactions->where('day', $day) as $t) {
                $key = "{$t->type}-{$t->payment_type}";
                $data[$key][array_search($day, $days->all())] = $t->total;
            }
        }
    
        $maintenance_payments = DB::table('maintenance_payments')
            ->select(
                DB::raw("DATE(created_at) as day"),
                'status',
                DB::raw("SUM(paid_amount + dues_amount + late_fine) as total")
            )
            ->where('building_id', $building_id)
            ->groupBy('day', 'status')
            ->orderBy('day', 'asc')
            ->get();
        
        // Get all unique days
        $maintenance_days = $maintenance_payments->pluck('day')->unique()->values();
        
        // Get all unique statuses dynamically (e.g., Paid, Unpaid)
        $statuses = $maintenance_payments->pluck('status')->unique()->values();
        
        // Initialize the result array dynamically
        $maintenance_data = [];
        
        foreach ($statuses as $status) {
            $maintenance_data[$status] = array_fill(0, count($maintenance_days), 0);
        }
        
        // Fill the data
        foreach ($maintenance_payments as $payment) {
            $dayIndex = $maintenance_days->search($payment->day);
            if ($dayIndex !== false) {
                $maintenance_data[$payment->status][$dayIndex] = $payment->total;
            }
        }
        
        // Prepare final output
        $response = [
            'maintenance_labels' => $maintenance_days->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray(),
            'maintenance_data' => $maintenance_data,
        ];


        
        $essentialPayments = DB::table('essential_payments')
            ->select(
                DB::raw("DATE(created_at) as day"),
                DB::raw("SUM(paid_amount) as total_paid")
            )
            ->where('building_id', $building_id)
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get();
        
        $essential_days = $essentialPayments->pluck('day')->unique()->values();
        $essential_data = [];
        
        foreach ($essential_days as $day) {
            $record = $essentialPayments->firstWhere('day', $day);
            $essential_data[] = $record ? $record->total_paid : 0;
        }
        
        $payments = DB::table('payments')
            ->select(
                DB::raw("DATE(created_at) as day"),
                DB::raw("SUM(amount) as total_paid")
            )
            ->where('building_id', $building_id)
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get();
        
        $payment_days = $payments->pluck('day')->unique()->values();
        $payment_data = [];
        
        foreach ($payment_days as $day) {
            $record = $payments->firstWhere('day', $day);
            $payment_data[] = $record ? $record->total_paid : 0;
        }
        
        $expenses = DB::table('expenses')
            ->select(
                DB::raw("DATE(date) as day"),
                'type',
                'payment_type',
                DB::raw("SUM(amount) as total")
            )
            ->where('building_id', $building_id)
            ->groupBy('day', 'type', 'payment_type')
            ->orderBy('day', 'asc')
            ->get();
        
        $expense_days = $expenses->pluck('day')->unique()->values();
        
        $expense_data = [
            'Credit-InBank' => [],
            'Credit-InHand' => [],
            'Debit-InBank' => [],
            'Debit-InHand' => [],
        ];
        
        foreach ($expense_days as $day) {
            foreach ($expense_data as $key => $val) {
                $expense_data[$key][] = 0; // initialize
            }
        
            foreach ($expenses->where('day', $day) as $e) {
                $key = "{$e->type}-{$e->payment_type}";
                $expense_data[$key][array_search($day, $expense_days->all())] = $e->total;
            }
        }


        return view('admin.dashboard', [
            'labels' => $days->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray(),
            'data' => $data,
    
            'maintenance_labels' => $maintenance_days->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray(),
            'maintenance_data' => $maintenance_data,

            
            'essential_labels' => $essential_days->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray(),
            'essential_data' => $essential_data,
            
            'payment_labels' => $payment_days->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray(),
            'payment_data' => $payment_data,
            
            'expense_labels' => $expense_days->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toArray(),
            'expense_data' => $expense_data,
        ]);
    }

    
    public function profile()
    {
        $customer = Auth::User();
        return view('admin.profile',compact('customer'));
    }
    
    public function update_profile(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email' => [
                    'required',
                    'email',
                    'max:255',
                    // Removed uniqueness validation to allow same email in multiple buildings
                    function ($attribute, $value, $fail) {
                        // Check for suspicious email patterns
                        $domain = explode('@', $value)[1] ?? '';
                        
                        // Reject emails with too many dots in domain (more than 6 levels)
                        if (substr_count($domain, '.') > 2) {
                            $fail('The email domain appears to be invalid (too many subdomains).');
                        }
                        
                        // Reject emails with excessively long domains (over 50 characters)
                        if (strlen($domain) > 50) {
                            $fail('The email domain is too long.');
                        }
                        
                        // Reject emails with suspicious patterns
                        if (preg_match('/\.(test|example|invalid|localhost)$/i', $domain)) {
                            $fail('Please use a valid email address.');
                        }
                    },
                ],
            'phone' => [
                'required',
                'regex:/^(?!([0-9])\1{9})[6-9]\d{9}$/',
                'unique:users,phone,' . Auth::id(),
                function ($attribute, $value, $fail) {
                    // Additional check for phone number uniqueness with custom message
                    $existingUser = User::where('phone', $value)
                        ->where('id', '!=', Auth::id())
                        ->first();
                    
                    if ($existingUser) {
                        $fail('The provided phone number is already registered.');
                    }
                },
            ],
            'gender' => 'required|in:Male,Female,Others',
           'address' => [
                    'required',
                    'string',
                    'max:500',
                    'regex:/^(?=.*[A-Za-z])[A-Za-z0-9\s,\.\-#\/]+$/',
                ],
            'photo' => 'nullable|image|max:2048',
        ];

    
        $validation = \Validator::make( $request->all(), $rules );
        if( $validation->fails() ) {
            return redirect()->back()->with('error',$validation->errors()->first());
        }
        $customer = Auth::User();
        
        // if($request->hasFile('photo')) {
        //     $file= $request->file('photo');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($customer->getPhotoFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/profiles/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $customer->photo = $filename;
        // }
        
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($customer->photo_filename)) {
                $file_path = public_path('images/' . $customer->photo_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'profiles/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/profiles/'), $filename);
            $customer->photo = $filename;
        }
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->gender = $request->gender;
        $customer->address = $request->address;
        $customer->save();
        return redirect()->back()->with('success','Profile Updated');
    }
    
    public function change_password(Request $request)
    {
        $request->validate([
                'current_password' => 'required',
                'password' =>[
                'required',
                'string',
                'min:8',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
                'confirmed',
                function ($attribute, $value, $fail) {
                    if (Hash::check($value, Auth::user()->password)) {
                        $fail('The new password must not be the same as the current password.');
                    }
                },
            ],
                'password_confirmation' => 'required',
            ]);
            
        $user = Auth::User();
        if (Hash::check($request->current_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();
            // Auth::logout();
            return redirect()->back()->with('success','Password updated successfully');
        }
        return redirect()->back()->with('error','Invalid current password');
    }
    
    public function update_user_status(Request $request)
    {
        // If building_user_id is provided, update per-role status
        if ($request->has('building_user_id') && $request->building_user_id) {
            $buildingUser = BuildingUser::find($request->building_user_id);
            
            if (!$buildingUser) {
                return response()->json(['msg' => 'error', 'message' => 'Role assignment not found'], 404);
            }
            
            // Toggle status
            $buildingUser->status = ($buildingUser->status === 'Active') ? 'Inactive' : 'Active';
            $buildingUser->save();
            
            return response()->json(['msg' => 'success', 'new_status' => $buildingUser->status], 200);
        } else {
            // Fallback to old logic for backward compatibility
            $user = User::where('id', $request->id)->withTrashed()->first();
            if ($user->status == 'Active') {
                $user->status = 'Inactive';
            } else {
                $user->status = 'Active';
            }
            $user->save();
            return response()->json(['msg' => 'success'], 200);
        }
    }
    
    public function update_document_status(Request $request)
    {
        $user = User::where('id',$request->id)->withTrashed()->first();
        if($user->email_sent_document_status == 'Verified'){
            $user->email_sent_document_status = 'Pending';
        }else{
            $user->email_sent_document_status = 'Verified';
        }
        $user->save();
        return response()->json([
            'msg' => 'success'
        ],200);
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['selected_role_id', 'current_building_id']);
        Auth::logout();
        return redirect('/');
    }
    

    public function users()
    {
        if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::User()->hasPermission('custom.information'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        $building = Auth::User()->building;

        // Get all users with their building-role assignments in this building, filtering by 'user' role
        $buildingUsers = BuildingUser::where('building_id', Auth::User()->building_id)
            ->with(['user' => function($query) {
                $query->withTrashed();
            }, 'role'])
            ->get();
        
        // Filter to get only 'user' role assignments
        $filteredBuildingUsers = $buildingUsers->filter(function($bu) {
            return $bu->role && $bu->role->slug === 'user';
        });
        
        // Create a collection of user-role assignments (one row per assignment)
        $users = collect();
        $directUserIds = collect(); // Track which users are already in direct list
        
        foreach ($filteredBuildingUsers as $buildingUser) {
            if ($buildingUser->user) {
                $userData = $buildingUser->user->toArray();
                $userData['building_user_id'] = $buildingUser->id; // Store assignment ID for actions
                $userData['role_name'] = $buildingUser->role ? $buildingUser->role->name : 'User';
                $userData['role_id'] = $buildingUser->role_id;
                $userData['status'] = $buildingUser->status; // Use status from assignment, not user
                $userData['source'] = 'direct'; // Mark as direct user
                $users->push((object)$userData);
                $directUserIds->push($buildingUser->user_id);
            }
        }
        
        // Additionally, fetch users who are owners or tenants of flats in this building
        $flatUsers = \App\Models\Flat::where('building_id', $building->id)
            ->select('owner_id', 'tanent_id')
            ->get()
            ->pluck('owner_id', 'tanent_id')
            ->flatten()
            ->filter()
            ->unique();
        
        // Get user details for flat owners/tenants not already in direct users
        $flatUserIds = $flatUsers->diff($directUserIds)->toArray();
        if (!empty($flatUserIds)) {
            $flatOwnersTenants = \App\Models\User::whereIn('id', $flatUserIds)
                ->withTrashed()
                ->get();
            
            foreach ($flatOwnersTenants as $user) {
                $userData = $user->toArray();
                $userData['building_user_id'] = null; // No building user assignment
                $userData['role_name'] = 'Flat Owner/Tenant'; // Custom role label
                $userData['role_id'] = null;
                $userData['status'] = 'Active'; // Default status for flat users
                $userData['source'] = 'flat'; // Mark as flat user
                $users->push((object)$userData);
                $directUserIds->push($user->id); // Add to avoid duplicates
            }
        }
        
        // Count active direct assignments only (not flat-only users)
        $active_count = $filteredBuildingUsers->filter(function($bu) {
            return $bu->status === 'Active' && $bu->user && $bu->user->deleted_at == null;
        })->count();
        
        // Total count is all users (direct + flat owners/tenants)
        $total_count = $users->count();
        
        $login_limit = Auth::user()->building->no_of_logins;
        $cities = City::all();
        
        return view('admin.user.index',compact('users','cities','active_count','total_count','login_limit'));
    }
    
  public function other_users()
    {
        if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::User()->hasPermission('custom.information'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        $building = Auth::User()->building;

        // Get all building-role assignments excluding the default 'user' role
        $buildingUsers = BuildingUser::where('building_id', Auth::User()->building_id)
            ->with(['user' => function($query) {
                $query->withTrashed();
            }, 'role'])
            ->get();
        
        // Filter to get only non-standard role assignments
        $filteredBuildingUsers = $buildingUsers->filter(function($bu) {
            return $bu->role && $bu->role->slug !== 'user';
        });
        
        // Create a collection of role assignments (one row per assignment)
        $users = collect();
        foreach ($filteredBuildingUsers as $buildingUser) {
            if ($buildingUser->user) {
                $userData = $buildingUser->user->toArray();
                $userData['building_user_id'] = $buildingUser->id;
                $userData['role_name'] = $buildingUser->role ? $buildingUser->role->name : 'Other';
                $userData['role_id'] = $buildingUser->role_id;
                $userData['status'] = $buildingUser->status;
                $users->push((object)$userData);
            }
        }
        
        // Count active role assignments
        $other_count = $filteredBuildingUsers->where('status', 'Active')->filter(function($bu) {
            return $bu->user && $bu->user->deleted_at == null;
        })->count();
        
        $other_limit = Auth::user()->building->no_of_other_users;
        $cities = City::all();
        
        return view('admin.user.other_users',compact('users','cities','other_count','other_limit'));
    }
    // public function get_user_building_info(Request $request) {
    //     try {
    //         $user = User::with(['departments.building.builder', 'departments.role'])->find($request->user_id);
    //         $currentBuildingId = Auth::user()->building_id;
            
    //         if (!$user) {
    //             return response()->json(['success' => false, 'message' => 'User not found']);
    //         }

    //         $userData = [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'email' => $user->email,
    //             'phone' => $user->phone
    //         ];

    //         $buildings = [];
    //         foreach ($user->departments as $department) {
    //             if ($department->building) {
    //                 $buildings[] = [
    //                     'id' => $department->building->id,
    //                     'name' => $department->building->name,
    //                     'builder_name' => $department->building->builder ? $department->building->builder->name : 'N/A',
    //                     'role' => $department->role ? $department->role->name : 'User',
    //                     'is_current' => $department->building_id == $currentBuildingId
    //                 ];
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'user' => $userData,
    //             'buildings' => $buildings
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'message' => 'Error loading user information']);
    //     }
    // }
    public function get_user_building_info(Request $request) {
        try {
            $user = User::find($request->user_id);
            $currentBuildingId = Auth::user()->building_id;
            $buildingUserId = $request->building_user_id;
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found']);
            }

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone
            ];

            $buildings = [];
            if ($buildingUserId) {
                $buildingUser = \App\Models\BuildingUser::with(['building.builder', 'role'])->find($buildingUserId);
                if ($buildingUser && $buildingUser->building) {
                    $buildings[] = [
                        'id' => $buildingUser->building->id,
                        'building_user_id' => $buildingUser->id,
                        'name' => $buildingUser->building->name,
                        'builder_name' => $buildingUser->building->builder ? $buildingUser->building->builder->name : 'N/A',
                        'role' => $buildingUser->role ? $buildingUser->role->name : 'User',
                        'is_current' => $buildingUser->building_id == $currentBuildingId
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'user' => $userData,
                'buildings' => $buildings
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error loading user information']);
        }
    }
  public function delete_user_enhanced(Request $request) {
    //   dd("qwefdwef");
        try {
            $user = User::find($request->id);
            $currentBuildingId = Auth::user()->building_id;
            
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found']);
            }

            if ($request->action == 'delete') {
                // If building_user_id is provided, delete just that specific role assignment
                if ($request->has('building_user_id') && $request->building_user_id) {
                    $buildingUser = BuildingUser::find($request->building_user_id);
                    
                    if (!$buildingUser) {
                        return response()->json(['success' => false, 'message' => 'Role assignment not found']);
                    }
                    
                    // Ensure we're only deleting from current building
                    if ($buildingUser->building_id != $currentBuildingId) {
                        return response()->json(['success' => false, 'message' => 'Unauthorized action']);
                    }
                    
                    $roleName = $buildingUser->role ? $buildingUser->role->name : 'Unknown';
                    
                    // Delete the specific role assignment
                    $buildingUser->forceDelete();
                    
                    // If role is guard-related, also delete associated guard data
                    if (strtolower($roleName) === 'guard' || strtolower($roleName) === 'security') {
                        Guard::where('user_id', $user->id)
                             ->where('building_id', $currentBuildingId)
                             ->forceDelete();
                    }
                    
                    $message = 'User\'s "' . $roleName . '" role assignment has been deleted successfully.';
                } else {
                    // Fallback to original logic if no building_user_id provided
                    $deleteOption = $request->delete_option ?? 'delete_all';
                    
                    if ($user->role == 'BA') {
                        BuildingUser::where('user_id', $user->id)
                                  ->where('building_id', $currentBuildingId)
                                  ->forceDelete();
                        
                        Guard::where('user_id', $user->id)
                             ->where('building_id', $currentBuildingId)
                             ->forceDelete();
                        
                        $message = 'Building Admin has been removed from the current building.';
                    } else {
                        // Handle other delete options...
                        $message = 'User assignment has been deleted.';
                    }
                }
            } else {
                // Restore user
                $user->status = 'Active';
                $user->save();
                $user->restore();
                
                $message = 'User has been restored successfully.';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }




 public function show_user($id,$building_user_id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $customer = User::where('id', $id)
            ->where('created_by', Auth::User()->building_id)
            ->withTrashed()
            ->with('departments.role')
            ->first();
        if(!$customer){
            return redirect('/users');
        }
        // Find the specific department assignment for this building_user_id
        $selectedDepartment = null;
        if ($building_user_id && $customer->departments) {
            $selectedDepartment = $customer->departments->first(function($dep) use ($building_user_id) {
                return $dep->id == $building_user_id;
            });
        }

        // Query BuildingUser by id, get role_id and role name
        $buildingUser = \App\Models\BuildingUser::with('role')->find($building_user_id);
        $role_id = $buildingUser ? $buildingUser->role_id : null;
        $role_name = ($buildingUser && $buildingUser->role) ? $buildingUser->role->name : null;
// dd($role_name);
        // All roles in the current building
        $allBuildingRoles = \App\Models\Role::where('building_id', Auth::User()->building_id)->get();
        return view('admin.user.show', compact('customer',  'allBuildingRoles', 'selectedDepartment', 'role_id', 'role_name'));
    }

//   public function show_user($id)
//     {
//         if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') )
//         {
//             //
//         }else{
//             return redirect('permission-denied')->with('error','Permission denied!');
//         }
//         $customer = User::where('id', $id)
//             ->where('created_by', Auth::User()->building_id)
//             ->withTrashed()
//             ->with('departments.role')
//             ->first();
//         if(!$customer){
//             return redirect('/users');
//         }
//         // Current building's non-'user' roles for this user
//         $currentBuildingRoles = $customer->departments->filter(function($dep) {
//             return $dep->building_id == Auth::User()->building_id && $dep->role && $dep->role->slug !== 'user';
//         });
//         // All roles in the current building
//         $allBuildingRoles = \App\Models\Role::where('building_id', Auth::User()->building_id)->get();
//         return view('admin.user.show', compact('customer', 'currentBuildingRoles', 'allBuildingRoles'));
//     }
    
  public function store_user(Request $request)
    {
        // dd($request->all());
        if (Auth::User()->role != 'BA' && !Auth::User()->hasRole('issue')) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }
       
        // Define the validation rules - different for existing users
        if ($request->add_existing && $request->existing_user_id) {
            // Minimal validation for adding existing users - no email/phone uniqueness check
            $rules = [
                'existing_user_id' => 'required|exists:users,id',
                'created_type' => 'required|in:direct,other',
            ];
        } else {
            // Full validation for new users or updates
            $rules = [
                'first_name' => 'required|max:30',
                'last_name' => 'required|max:30',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    // Removed uniqueness validation to allow same email in multiple buildings
                    function ($attribute, $value, $fail) {
                        // Check for suspicious email patterns
                        $domain = explode('@', $value)[1] ?? '';
                        
                        // Reject emails with too many dots in domain (more than 6 levels)
                        if (substr_count($domain, '.') > 2) {
                            $fail('The email domain appears to be invalid (too many subdomains).');
                        }
                        
                        // Reject emails with excessively long domains (over 50 characters)
                        if (strlen($domain) > 50) {
                            $fail('The email domain is too long.');
                        }
                        
                        // Reject emails with suspicious patterns
                        if (preg_match('/\.(test|example|invalid|localhost)$/i', $domain)) {
                            $fail('Please use a valid email address.');
                        }
                    },
                ],
                'phone' => [
    'required',
    'regex:/^[6-9]\d{9}$/',
    function ($attribute, $value, $fail) use ($request) {

        // 1. Identical digits (9999999999)
        if (preg_match('/^(\d)\1{9}$/', $value)) {
            $fail('Phone number cannot have all identical digits.');
        }

        // 2. Sequential numbers (1234567890, 0987654321)
        $isAscending = true;
        $isDescending = true;

        for ($i = 0; $i < 9; $i++) {
            if ((int)$value[$i + 1] !== (int)$value[$i] + 1) {
                $isAscending = false;
            }
            if ((int)$value[$i + 1] !== (int)$value[$i] - 1) {
                $isDescending = false;
            }
        }

        if ($isAscending || $isDescending) {
            $fail('Phone number cannot be in sequential order.');
        }

        // 3. Repeating patterns (1212121212)
        if (
            preg_match('/^(\d{2})\1{4}$/', $value) ||
            preg_match('/^(\d{3})\1{3}\d$/', $value) ||
            preg_match('/^(\d{4})\1{2}\d{2}$/', $value)
        ) {
            $fail('Phone number cannot have repeating patterns.');
        }

        // 4. Common invalid numbers
        $invalidNumbers = [
            '0000000000', '1111111111', '2222222222',
            '1234567890', '0987654321', '9876543210'
        ];

        if (in_array($value, $invalidNumbers)) {
            $fail('Please enter a valid phone number.');
        }

        // 5. Uniqueness in building check
        $query = User::where('phone', $value);

        if ($request->id) {
            $query->where('id', '!=', $request->id);
        }

        $existingUser = $query->first();

        if ($existingUser) {
            $currentBuildingId = Auth::User()->building_id;

            // Check if user already has 'user' role in this building
            // Allow if they have other roles (like president) but not this specific role
            $userRole = \App\Models\Role::where('building_id', $currentBuildingId)
                ->where('slug', 'user')
                ->first();
            
            $alreadyHasUserRole = BuildingUser::where('building_id', $currentBuildingId)
                ->where('user_id', $existingUser->id)
                ->where('role_id', $userRole ? $userRole->id : null)
                ->whereNull('deleted_at')
                ->exists();

            if ($alreadyHasUserRole) {
                $fail('The User is already registered as a user.');
            }
        }
    }
],
                'gender' => 'required|in:Male,Female,Others',
                'status' => 'required|in:Active,Inactive',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'password' => [
                    $request->id ? 'nullable' : 'required',
                    'string',
                    'min:8',
                    'max:14',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*#?&]/',
                    'regex:/^\S*$/', // No spaces allowed
                ],
                'role' => 'required|string',
            ];
        }

        // Define custom error messages
        $messages = [
            'password.required' => 'Password must be 8–14 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character. No spaces.',
            'password.min' => 'Password must be 8–14 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character. No spaces.',
            'password.max' => 'Password must be 8–14 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character. No spaces.',
            'password.regex' => 'Password must be 8–14 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character. No spaces.',
        ];

        // Validate the request
        $validation = Validator::make($request->all(), $rules, $messages);
    
        if ($validation->fails()) {
            // Add debugging info for existing user mode
            $errorMsg = $validation->errors()->first();
            if ($request->add_existing && $request->existing_user_id) {
                $errorMsg = 'Error adding existing user: ' . $errorMsg;
            }
            return redirect()->back()->with('error', $errorMsg);
        }
        
        // Handle existing user or create new user.
        // dd($request->all());
        if ($request->existing_user_id && $request->add_existing) {
            // dd($request->all());
            //   dd($request->all());
            // Use existing user - add to current building via BuildingUser relationship
            $existingUser = User::findOrFail($request->existing_user_id);
            
            // Allow adding additional roles to users who already have roles
            // No need to check if user already exists - they can have multiple roles
            $currentBuildingId = Auth::User()->building_id;
            
            // Create BuildingUser relationship instead of duplicate User record
            $defaultUserRole = $this->getDefaultUserRole($currentBuildingId);
            //  dd($defaultUserRole);
         $exists = BuildingUser::where('building_id', $currentBuildingId)
        
    ->where('role_id', $defaultUserRole->id)
    ->where('user_id',$request->existing_user_id)
    ->exists();
    // dd($exists);
            // dd($defaultUserRole);
            if($exists){
                        return redirect()->back()->with('error', 'User is already assigned as a user in this building.');
            }
            $buildingUser = new BuildingUser();
            $buildingUser->building_id = $currentBuildingId;
            $buildingUser->user_id = $existingUser->id;
            $buildingUser->role_id = $defaultUserRole->id;
            // Use requested status if provided (editing assignment), otherwise default Active
            $buildingUser->status = $request->status ?? 'Active';
            $buildingUser->save();
            
            $user = $existingUser; // Reference the existing user
            $msg = 'Existing User Added to Building';
            $isExistingUser = true;
        } elseif ($request->id) {
            // Update existing user
            $user = User::findOrFail($request->id);
            $msg = 'User Updated';
            $isExistingUser = false;
        } else {
            // Check if user with this email already exists globally
            $existingGlobalUser = User::where('email', $request->email)->first();
            
            if ($existingGlobalUser) {
                // User exists globally, check if already has 'user' role in this building
                // Allow if they have other roles (like president) but not this specific role
                $currentBuildingId = Auth::User()->building_id;
                $userRole = \App\Models\Role::where('building_id', $currentBuildingId)
                    ->where('slug', 'user')
                    ->first();
                // dd("wefwef");
                $alreadyExists = BuildingUser::where('building_id', $currentBuildingId)
                    ->where('user_id', $existingGlobalUser->id)
                    ->where('role_id', $userRole ? $userRole->id : null)
                    ->whereNull('deleted_at')
                    ->exists();
                
                if ($alreadyExists) {
                    return redirect()->back()->with('error', 'User is already assigned as a user in this building.');
                }
                
                // Add existing user to current building
                $defaultUserRole = $this->getDefaultUserRole($currentBuildingId);
                $buildingUser = new BuildingUser();
                $buildingUser->building_id = $currentBuildingId;
                $buildingUser->user_id = $existingGlobalUser->id;
                $buildingUser->role_id = $defaultUserRole->id;
                $buildingUser->save();
                
                $user = $existingGlobalUser;
                $msg = 'Existing User Added to Building';
                $isExistingUser = true;
            } else {
                // Check if user with this phone number already exists globally
                $existingPhoneUser = User::where('phone', $request->phone)->first();
                
                if ($existingPhoneUser) {
                    // User with phone exists globally, check if already has 'user' role in this building
                    // Allow if they have other roles (like president) but not this specific role
                    $currentBuildingId = Auth::User()->building_id;
                    $userRole = \App\Models\Role::where('building_id', $currentBuildingId)
                        ->where('slug', 'user')
                        ->first();
                    
                    $phoneAlreadyExists = BuildingUser::where('building_id', $currentBuildingId)
                        ->where('user_id', $existingPhoneUser->id)
                        ->where('role_id', $userRole ? $userRole->id : null)
                        ->whereNull('deleted_at')
                        ->exists();
                    
                    if ($phoneAlreadyExists) {
                        return redirect()->back()->with('error', 'User is already assigned as a user in this building.');
                    }
                    
                    // Add existing user to current building
                    $defaultUserRole = $this->getDefaultUserRole($currentBuildingId);
                    $buildingUser = new BuildingUser();
                    $buildingUser->building_id = $currentBuildingId;
                    $buildingUser->user_id = $existingPhoneUser->id;
                    $buildingUser->role_id = $defaultUserRole->id;
                    $buildingUser->save();
                    
                    $user = $existingPhoneUser;
                    $msg = 'Existing User Added to Building';
                    $isExistingUser = true;
                } else {
                    // Create new user
                    $user = new User();
                    $msg = 'User Added';
                    $isExistingUser = false;
                }
            }
        }
        
        // Check limits only for new users (existing users already handled above)
        if(!$request->id && !$isExistingUser){
            // Count existing users through BuildingUser relationships
            if($request->created_type == 'direct'){
                // Count users with 'user' role (direct users)
                $userRole = $this->getDefaultUserRole(Auth::User()->building_id);
                $created_counts = BuildingUser::where('building_id', Auth::User()->building_id)
                    ->where('role_id', $userRole->id)
                    ->count();
                $login_limit = Auth::user()->building->no_of_logins;
                if($created_counts >= $login_limit){
                    return redirect()->back()->with('error', 'No of login limit is exceeded');
                }
            }elseif($request->created_type == 'other'){
                $created_counts = \App\Models\User::where('created_by', Auth::User()->building_id)->where('created_type','other')->count(); 
                $other_users = Auth::user()->building->no_of_other_users;
                if($created_counts >= $other_users){
                    return redirect()->back()->with('error', 'No of other users limit is exceeded');
                }
            }
        }
                  
        // Assign user data (skip for existing users - they're already set above)
        if (!$isExistingUser) {
            $user->role = $request->role ?? 'user';
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->gender = $request->gender;
            $user->city_id = $request->city_id;
            $user->address = $request->address;
            $user->company_name = $request->company_name;
            // Do NOT set user.status here; use building_users.status instead
            $user->created_by = Auth::User()->building_id;
            $user->created_type = $request->created_type;
            $user->building_id = Auth::User()->building_id;
        }
        
        // Handle block assignments for Presidents
        if ($request->has('assigned_blocks') && is_array($request->assigned_blocks)) {
            $user->assigned_blocks = $request->assigned_blocks;
        }
        
        
        // Handle image upload
        // dd($request->all());
       if ($request->hasFile('image')) {
                $file = $request->file('image');
                $allowedfileExtension = ['jpeg', 'jpg', 'png'];
                $extension = $file->getClientOriginalExtension();
                
                // Delete old image if exists
                if (!empty($user->photo)) {
                    $file_path = public_path('images/' . $user->photo);
                    if (is_file($file_path)) {
                        unlink($file_path);
                    }
                }

                $filename = uniqid() . '.' . $extension;
                $path = $file->move(public_path('/images'), $filename);
                $user->photo = $filename;
            }
        
        $user->city_id = $request->city_id;
        $user->address = $request->address;
        
        // Only set a password if provided (and not for existing users - they keep their password)
        if ($request->password && !$isExistingUser) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        // Create BuildingUser relationship for new users (not existing or role-based users)
        // NEW users get Active status
        if (!$isExistingUser && !$request->role_id && $request->created_type == 'direct' && !$request->id) {
            $defaultUserRole = $this->getDefaultUserRole(Auth::User()->building_id);
            $buildingUser = new BuildingUser();
            $buildingUser->building_id = Auth::User()->building_id;
            $buildingUser->user_id = $user->id;
            $buildingUser->role_id = $defaultUserRole->id;
            $buildingUser->status = $request->status ?? 'Active';
            $buildingUser->save();
        }

        if ($request->password) {
            $setting = Setting::first();
            $logo = $setting->logo;
            $info = array(
                'user' => $user,
                'password' => $request->password,
                'logo' => $logo,
            );
            // send email
            try {
                Mail::send('email.password', $info, function ($message) use ($user) {
                    $message->to($user->email, $user->name)
                            ->subject('Your MyFlatInfo Account Has Been Created');
                });
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to queue email. ' . $e->getMessage()
                ], 500);
            }
        }
        
        if($request->role_id){
            // Check if this is an update (user has ID) or new user
            if ($request->id) {
                // Update existing BuildingUser relationship
                $building_user = BuildingUser::where('building_id', Auth::User()->building_id)
                    ->where('user_id', $user->id)
                    ->first();
                
                if ($building_user) {
                    // Update existing relationship
                    $building_user->role_id = $request->role_id;
                    if ($request->has('status')) {
                        $building_user->status = $request->status;
                    }
                    $building_user->save();
                } else {
                    // Create new relationship if it doesn't exist
                    $building_user = new BuildingUser();
                    $building_user->building_id = Auth::User()->building_id;
                    $building_user->role_id = $request->role_id;
                    $building_user->user_id = $user->id;
                    $building_user->status = $request->status ?? 'Active';
                    $building_user->save();
                }
            } else {
                // Create new BuildingUser relationship for new users
                $building_user = new BuildingUser();
                $building_user->building_id = Auth::User()->building_id;
                $building_user->role_id = $request->role_id;
                $building_user->user_id = $user->id;
                $building_user->status = $request->status ?? 'Active';
                $building_user->save();
            }
        }

        // If editing a specific building_user assignment, update its status as provided
        if ($request->has('building_user_id') && $request->building_user_id) {
            $bu = BuildingUser::find($request->building_user_id);
            if ($bu) {
                if ($request->has('status')) {
                    $bu->status = $request->status;
                }
                // If role_id provided, keep it in sync
                if ($request->has('role_id')) {
                    $bu->role_id = $request->role_id;
                }
                $bu->save();
            }
        }

        return redirect()->back()->with('success', $msg);
    }

//     public function store_user(Request $request)
//     {
//             //   dd($request->all());
//         if (Auth::User()->role != 'BA' && !Auth::User()->hasRole('issue')) {
//             return redirect('permission-denied')->with('error', 'Permission denied!');
//         }
       
//         // Define the validation rules - different for existing users
//         if ($request->add_existing && $request->existing_user_id) {
//             // Minimal validation for adding existing users - no email/phone uniqueness check
//             $rules = [
//                 'existing_user_id' => 'required|exists:users,id',
//                 'created_type' => 'required|in:direct,other',
//             ];
//         } else {
//             // Full validation for new users or updates
//             $rules = [
//                 'first_name' => 'required|max:30',
//                 'last_name' => 'required|max:30',
//                 'email' => [
//                     'required',
//                     'email',
//                     'max:255',
//                     // Removed uniqueness validation to allow same email in multiple buildings
//                     function ($attribute, $value, $fail) {
//                         // Check for suspicious email patterns
//                         $domain = explode('@', $value)[1] ?? '';
                        
//                         // Reject emails with too many dots in domain (more than 6 levels)
//                         if (substr_count($domain, '.') > 2) {
//                             $fail('The email domain appears to be invalid (too many subdomains).');
//                         }
                        
//                         // Reject emails with excessively long domains (over 50 characters)
//                         if (strlen($domain) > 50) {
//                             $fail('The email domain is too long.');
//                         }
                        
//                         // Reject emails with suspicious patterns
//                         if (preg_match('/\.(test|example|invalid|localhost)$/i', $domain)) {
//                             $fail('Please use a valid email address.');
//                         }
//                     },
//                 ],
//                 'phone' => [
//     'required',
//     'regex:/^[6-9]\d{9}$/',
//     function ($attribute, $value, $fail) use ($request) {

//         // 1. Identical digits (9999999999)
//         if (preg_match('/^(\d)\1{9}$/', $value)) {
//             $fail('Phone number cannot have all identical digits.');
//         }

//         // 2. Sequential numbers (1234567890, 0987654321)
//         $isAscending = true;
//         $isDescending = true;

//         for ($i = 0; $i < 9; $i++) {
//             if ((int)$value[$i + 1] !== (int)$value[$i] + 1) {
//                 $isAscending = false;
//             }
//             if ((int)$value[$i + 1] !== (int)$value[$i] - 1) {
//                 $isDescending = false;
//             }
//         }

//         if ($isAscending || $isDescending) {
//             $fail('Phone number cannot be in sequential order.');
//         }

//         // 3. Repeating patterns (1212121212)
//         if (
//             preg_match('/^(\d{2})\1{4}$/', $value) ||
//             preg_match('/^(\d{3})\1{3}\d$/', $value) ||
//             preg_match('/^(\d{4})\1{2}\d{2}$/', $value)
//         ) {
//             $fail('Phone number cannot have repeating patterns.');
//         }

//         // 4. Common invalid numbers
//         $invalidNumbers = [
//             '0000000000', '1111111111', '2222222222',
//             '1234567890', '0987654321', '9876543210'
//         ];

//         if (in_array($value, $invalidNumbers)) {
//             $fail('Please enter a valid phone number.');
//         }

//         // 5. Uniqueness in building check
//         $query = User::where('phone', $value);

//         if ($request->id) {
//             $query->where('id', '!=', $request->id);
//         }

//         $existingUser = $query->first();

//         if ($existingUser) {
//             $currentBuildingId = Auth::User()->building_id;

//             $alreadyInBuilding = BuildingUser::where('building_id', $currentBuildingId)
//                 ->where('user_id', $existingUser->id)
//                 ->whereNull('deleted_at')
//                 ->exists();

//             if ($alreadyInBuilding) {
//                 $fail('The provided phone number is already registered.');
//             }
//         }
//     }
// ],
//                 'gender' => 'required|in:Male,Female,Others',
//                 'status' => 'required|in:Active,Inactive',
//                 'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
//                 'password' => [
//                     $request->id ? 'nullable' : 'required',
//                     'string',
//                     'min:8',
//                     'max:14',
//                     'regex:/[a-z]/',
//                     'regex:/[A-Z]/',
//                     'regex:/[0-9]/',
//                     'regex:/[@$!%*#?&]/',
//                     'regex:/^\S*$/', // No spaces allowed
//                 ],
//                 'role' => 'required|string',
//             ];
//         }

//         // Define custom error messages
//         $messages = [
//             'password.required' => 'Password must be 8–14 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character. No spaces.',
//             'password.min' => 'Password must be 8–14 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character. No spaces.',
//             'password.max' => 'Password must be 8–14 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character. No spaces.',
//             'password.regex' => 'Password must be 8–14 characters and include at least one uppercase letter, one lowercase letter, one number, and one special character. No spaces.',
//         ];

//         // Validate the request
//         $validation = Validator::make($request->all(), $rules, $messages);
    
//         if ($validation->fails()) {
//             // Add debugging info for existing user mode
//             $errorMsg = $validation->errors()->first();
//             if ($request->add_existing && $request->existing_user_id) {
//                 $errorMsg = 'Error adding existing user: ' . $errorMsg;
//             }
//             return redirect()->back()->with('error', $errorMsg);
//         }
        
//         // Handle existing user or create new user
//         if ($request->existing_user_id && $request->add_existing) {
//             // Use existing user - add to current building via BuildingUser relationship
//             $existingUser = User::findOrFail($request->existing_user_id);
            
//             // Check if user is already in this building via BuildingUser relationship
//             $currentBuildingId = Auth::User()->building_id;
//             $alreadyExists = BuildingUser::where('building_id', $currentBuildingId)
//                 ->where('user_id', $existingUser->id)
//                 ->whereNull('deleted_at')
//                 ->exists();
            
//             if ($alreadyExists) {
//                 return redirect()->back()->with('error', 'User is already added to this building');
//             }
            
//             // Create BuildingUser relationship instead of duplicate User record
//             $defaultUserRole = $this->getDefaultUserRole($currentBuildingId);
//             $buildingUser = new BuildingUser();
//             $buildingUser->building_id = $currentBuildingId;
//             $buildingUser->user_id = $existingUser->id;
//             $buildingUser->role_id = $defaultUserRole->id;
//             // Use requested status if provided (editing assignment), otherwise default Active
//             $buildingUser->status = $request->status ?? 'Active';
//             $buildingUser->save();
            
//             $user = $existingUser; // Reference the existing user
//             $msg = 'Existing User Added to Building';
//             $isExistingUser = true;
//         } elseif ($request->id) {
//             // Update existing user
//             $user = User::findOrFail($request->id);
//             $msg = 'User Updated';
//             $isExistingUser = false;
//         } else {
//             // Check if user with this email already exists globally
//             $existingGlobalUser = User::where('email', $request->email)->first();
            
//             if ($existingGlobalUser) {
//                 // User exists globally, check if already in this building
//                 $currentBuildingId = Auth::User()->building_id;
//                 $alreadyExists = BuildingUser::where('building_id', $currentBuildingId)
//                     ->where('user_id', $existingGlobalUser->id)
//                     ->whereNull('deleted_at')
//                     ->exists();
                
//                 if ($alreadyExists) {
//                     return redirect()->back()->with('error', 'The provided email address is already registered.');
//                 }
                
//                 // Add existing user to current building
//                 $defaultUserRole = $this->getDefaultUserRole($currentBuildingId);
//                 $buildingUser = new BuildingUser();
//                 $buildingUser->building_id = $currentBuildingId;
//                 $buildingUser->user_id = $existingGlobalUser->id;
//                 $buildingUser->role_id = $defaultUserRole->id;
//                 $buildingUser->save();
                
//                 $user = $existingGlobalUser;
//                 $msg = 'Existing User Added to Building';
//                 $isExistingUser = true;
//             } else {
//                 // Check if user with this phone number already exists globally
//                 $existingPhoneUser = User::where('phone', $request->phone)->first();
                
//                 if ($existingPhoneUser) {
//                     // User with phone exists globally, check if already in this building
//                     $currentBuildingId = Auth::User()->building_id;
//                     $phoneAlreadyExists = BuildingUser::where('building_id', $currentBuildingId)
//                         ->where('user_id', $existingPhoneUser->id)
//                         ->whereNull('deleted_at')
//                         ->exists();
                    
//                     if ($phoneAlreadyExists) {
//                         return redirect()->back()->with('error', 'The provided phone number is already registered.');
//                     }
                    
//                     // Add existing user to current building
//                     $defaultUserRole = $this->getDefaultUserRole($currentBuildingId);
//                     $buildingUser = new BuildingUser();
//                     $buildingUser->building_id = $currentBuildingId;
//                     $buildingUser->user_id = $existingPhoneUser->id;
//                     $buildingUser->role_id = $defaultUserRole->id;
//                     $buildingUser->save();
                    
//                     $user = $existingPhoneUser;
//                     $msg = 'Existing User Added to Building';
//                     $isExistingUser = true;
//                 } else {
//                     // Create new user
//                     $user = new User();
//                     $msg = 'User Added';
//                     $isExistingUser = false;
//                 }
//             }
//         }
        
//         // Check limits only for new users (existing users already handled above)
//         if(!$request->id && !$isExistingUser){
//             // Count existing users through BuildingUser relationships
//             if($request->created_type == 'direct'){
//                 // Count users with 'user' role (direct users)
//                 $userRole = $this->getDefaultUserRole(Auth::User()->building_id);
//                 $created_counts = BuildingUser::where('building_id', Auth::User()->building_id)
//                     ->where('role_id', $userRole->id)
//                     ->count();
//                 $login_limit = Auth::user()->building->no_of_logins;
//                 if($created_counts >= $login_limit){
//                     return redirect()->back()->with('error', 'No of login limit is exceeded');
//                 }
//             }elseif($request->created_type == 'other'){
//                 $created_counts = \App\Models\User::where('created_by', Auth::User()->building_id)->where('created_type','other')->withTrashed()->count(); 
//                 $other_users = Auth::user()->building->no_of_other_users;
//                 if($created_counts >= $other_users){
//                     return redirect()->back()->with('error', 'No of other users limit is exceeded');
//                 }
//             }
//         }
                  
//         // Assign user data (skip for existing users - they're already set above)
//         if (!$isExistingUser) {
//             $user->role = $request->role ?? 'user';
//             $user->first_name = $request->first_name;
//             $user->last_name = $request->last_name;
//             $user->email = $request->email;
//             $user->phone = $request->phone;
//             $user->gender = $request->gender;
//             $user->city_id = $request->city_id;
//             $user->address = $request->address;
//             $user->company_name = $request->company_name;
//             // $user->status = $request->status ?? 'Active';
//             $user->created_by = Auth::User()->building_id;
//             $user->created_type = $request->created_type;
//             $user->building_id = Auth::User()->building_id;
//         }
        
//         // Handle block assignments for Presidents
//         if ($request->has('assigned_blocks') && is_array($request->assigned_blocks)) {
//             $user->assigned_blocks = $request->assigned_blocks;
//         }
        
        
//         // Handle image upload
//         if ($request->hasFile('image')) {
//                 $file = $request->file('image');
//                 $allowedfileExtension = ['jpeg', 'jpg', 'png'];
//                 $extension = $file->getClientOriginalExtension();
                
//                 // Delete old image if exists
//                 if (!empty($user->photo)) {
//                     $file_path = public_path('images/' . $user->photo);
//                     if (is_file($file_path)) {
//                         unlink($file_path);
//                     }
//                 }

//                 $filename = uniqid() . '.' . $extension;
//                 $path = $file->move(public_path('/images'), $filename);
//                 $user->photo = $filename;
//             }
        
//         $user->city_id = $request->city_id;
//         $user->address = $request->address;
        
//         // Only set a password if provided (and not for existing users - they keep their password)
//         if ($request->password && !$isExistingUser) {
//             $user->password = Hash::make($request->password);
//         }
        
//         $user->save();

//         // Create BuildingUser relationship for new users (not existing or role-based users)
//         if (!$isExistingUser && !$request->role_id && $request->created_type == 'direct' && !$request->id) {
//             $defaultUserRole = $this->getDefaultUserRole(Auth::User()->building_id);
//             $buildingUser = new BuildingUser();
//             $buildingUser->building_id = Auth::User()->building_id;
//             $buildingUser->user_id = $user->id;
//             $buildingUser->role_id = $defaultUserRole->id;
//             $buildingUser->status = $request->status ?? 'Active';
//             $buildingUser->save();
//         }

//         if ($request->password) {
//             $setting = Setting::first();
//             $logo = $setting->logo;
//             $info = array(
//                 'user' => $user,
//                 'password' => $request->password,
//                 'logo' => $logo,
//             );
//             // send email
//             try {
//                 Mail::send('email.password', $info, function ($message) use ($user) {
//                     $message->to($user->email, $user->name)
//                             ->subject('Forget Password');
//                 });
//             } catch (\Exception $e) {
//                 return response()->json([
//                     'error' => 'Failed to queue email. ' . $e->getMessage()
//                 ], 500);
//             }
//         }
        
//         if($request->role_id){
//             // Check if this is an update (user has ID) or new user
//             if ($request->id) {
//                 // Update existing BuildingUser relationship
//                 $building_user = BuildingUser::where('building_id', Auth::User()->building_id)
//                     ->where('user_id', $user->id)
//                     ->first();
                
//                 if ($building_user) {
//                     // Update existing relationship
//                     $building_user->role_id = $request->role_id;
//                     if ($request->has('status')) {
//                         $building_user->status = $request->status;
//                     }
//                     $building_user->save();
//                 } else {
//                     // Create new relationship if it doesn't exist
//                     $building_user = new BuildingUser();
//                     $building_user->building_id = Auth::User()->building_id;
//                     $building_user->role_id = $request->role_id;
//                     $building_user->user_id = $user->id;
//                     $building_user->status = $request->status ?? 'Active';
//                     $building_user->save();
//                 }
//             } else {
//                 // Create new BuildingUser relationship for new users
//                 $building_user = new BuildingUser();
//                 $building_user->building_id = Auth::User()->building_id;
//                 $building_user->role_id = $request->role_id;
//                 $building_user->user_id = $user->id;
//                 $building_user->status = $request->status ?? 'Active';
//                 $building_user->save();
//             }
//         }

//         // If editing a specific building_user assignment, update its status as provided
//         if ($request->has('building_user_id') && $request->building_user_id) {
//             $bu = BuildingUser::find($request->building_user_id);
//             if ($bu) {
//                 if ($request->has('status')) {
//                     $bu->status = $request->status;
//                 }
//                 // If role_id provided, keep it in sync
//                 if ($request->has('role_id')) {
//                     $bu->role_id = $request->role_id;
//                 }
//                 $bu->save();
//             }
//         }

//         return redirect()->back()->with('success', $msg);
//     }

    public function downloadSample()
    {
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=users_upload_template.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];
    
        $columns = ['first_name','last_name','email','phone','gender','password'];
    
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Add a comment row explaining requirements
            fputcsv($file, [
                '# ALL FIELDS REQUIRED - Do not leave any field empty',
                '# Phone: 10 digits starting with 6,7,8,9',
                '# Password: Min 8 chars with A-Z, a-z, 0-9, @$!%*#?&',
                '# Gender: Male, Female, or Others',
                '', ''
            ]);
            
            // Example rows with different scenarios
            
            // Row 1: Male user
            fputcsv($file, [
                'John',                    // first_name - REQUIRED
                'Doe',                     // last_name - REQUIRED
                'john.doe@example.com',    // email - REQUIRED
                '9876543210',              // phone - REQUIRED (10 digits, start with 6-9)
                'Male',                    // gender - REQUIRED (Male/Female/Others)
                'Password@123'             // password - REQUIRED (min 8 chars with special)
            ]);
            
            // Row 2: Female user
            fputcsv($file, [
                'Jane',                    // first_name - REQUIRED
                'Smith',                   // last_name - REQUIRED
                'jane.smith@example.com',  // email - REQUIRED
                '9876543211',              // phone - REQUIRED
                'Female',                  // gender - REQUIRED
                'Password@456'             // password - REQUIRED
            ]);
            
            // Row 3: Other gender user
            fputcsv($file, [
                'Alex',                    // first_name - REQUIRED
                'Johnson',                 // last_name - REQUIRED
                'alex.johnson@example.com', // email - REQUIRED
                '9876543212',              // phone - REQUIRED
                'Others',                  // gender - REQUIRED
                'Password@789'             // password - REQUIRED
            ]);
            
            fclose($file);
        };
    
        return response()->stream($callback, 200, $headers);
    }

    public function bulkUpload(Request $request)
    {
        if (Auth::User()->role != 'BA' && !Auth::User()->hasRole('issue')) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }
        
        $request->validate([
            'bulk_file' => 'required|mimes:csv,txt,xlsx|max:2048',
        ]);
    
        $path = $request->file('bulk_file')->getRealPath();
        $data = array_map('str_getcsv', file($path));
    
        $header = $data[0];
        unset($data[0]);
    
        $rows = [];
        foreach ($data as $index => $row) {
            if (count($row) == count($header)) {
                // Clean and trim all values
                $cleanRow = array_map(function($value) {
                    return is_string($value) ? trim($value) : $value;
                }, $row);
                
                // Skip rows that start with # (comments)
                if (!empty($cleanRow[0]) && strpos($cleanRow[0], '#') === 0) {
                    continue;
                }
                
                // Skip completely empty rows
                if (empty(array_filter($cleanRow))) {
                    continue;
                }
                
                $rows[] = array_combine($header, $cleanRow);
            }
        }
        
        if (empty($rows)) {
            return redirect()->back()->with('error', 'No valid data found in the uploaded file.');
        }
        
        // Process bulk user creation
        return $this->bulkCreateNewUsers($rows, $request);
    }
    
    private function bulkAddExistingUsers($rows, $request)
    {
        $currentBuildingId = Auth::User()->building_id;
        $created_type = $request->input('created_type', 'direct');
        
        $results = [
            'added' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $rowData) {
                if (!isset($rowData['email']) || empty($rowData['email'])) {
                    $results['errors'][] = "Row " . ($index + 2) . ": Email is required";
                    continue;
                }
                
                $email = trim($rowData['email']);
                
                // Find existing user
                $existingUser = User::where('email', $email)->first();
                
                if (!$existingUser) {
                    $results['errors'][] = "Row " . ($index + 2) . ": User with email {$email} not found";
                    continue;
                }
                
                // Check if user already exists in this building
                $alreadyExists = User::where('email', $email)
                    ->where('created_by', $currentBuildingId)
                    ->exists();
                
                if ($alreadyExists) {
                    $results['skipped']++;
                    continue;
                }
                
                // Create new user record for this building
                $user = new User();
                $user->first_name = $existingUser->first_name;
                $user->last_name = $existingUser->last_name;
                $user->email = $existingUser->email;
                $user->phone = $existingUser->phone;
                $user->gender = $existingUser->gender;
                $user->password = $existingUser->password;
                $user->role = 'user';
                $user->status = 'Active';
                $user->created_by = $currentBuildingId;
                $user->created_type = $created_type;
                $user->building_id = $currentBuildingId;
                $user->save();
                
                $results['added']++;
            }
            
            DB::commit();
            
            $message = "Bulk upload completed! Added: {$results['added']}, Skipped: {$results['skipped']}";
            if (!empty($results['errors'])) {
                $message .= "\nErrors: " . implode(', ', array_slice($results['errors'], 0, 3));
                if (count($results['errors']) > 3) {
                    $message .= " and " . (count($results['errors']) - 3) . " more...";
                }
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }
    
    private function bulkCreateNewUsers($rows, $request)
    {
        $emails = collect($rows)->pluck('email')->filter();
        $phones = collect($rows)->pluck('phone')->filter();
    
        $duplicateEmails = $emails->duplicates();
        $duplicatePhones = $phones->duplicates();
    
        if ($duplicateEmails->isNotEmpty() || $duplicatePhones->isNotEmpty()) {
            $message = "Please remove duplicate emails and phone numbers.";
    
            if ($duplicateEmails->isNotEmpty()) {
                $message .= "\nEmails: " . $duplicateEmails->implode(', ');
            }
    
            if ($duplicatePhones->isNotEmpty()) {
                $message .= "\nPhones: " . $duplicatePhones->implode(', ');
            }
    
            return redirect()->back()->with('error', 'Upload failed with duplicates: ' . $message);
        }
        // Determine type: direct or other
        $created_type = $request->input('created_type', 'direct');
        $uploading_count = count($rows);
        if ($created_type == 'direct') {
            // Count existing direct users through BuildingUser relationships
            // Count users with 'user' role (direct users)
            $userRole = $this->getDefaultUserRole(Auth::User()->building_id);
            $created_counts = BuildingUser::where('building_id', Auth::User()->building_id)
                ->where('role_id', $userRole->id)
                ->count();
            $login_limit = Auth::user()->building->no_of_logins;
            if (($created_counts + $uploading_count) > $login_limit) {
                return redirect()->back()->with('error', 'No of login limit is exceeded');
            }
        } elseif ($created_type == 'other') {
            $created_counts = \App\Models\User::where('created_by', Auth::User()->building_id)->where('created_type','other')->withTrashed()->count(); 
        //    dd($ceated_counts);
            $other_users = Auth::user()->building->no_of_other_users;
            if (($created_counts + $uploading_count) > $other_users) {
                return redirect()->back()->with('error', 'No of other users limit is exceeded');
            }
        }
        foreach ($rows as $index => $rowData) {
            $rules = [
                'first_name' => 'required|max:30',
                'last_name'  => 'required|max:30',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],
                'phone' => [
                    'required',
                    'regex:/^[6-9]\d{9}$/',
                    function ($attribute, $value, $fail) {
                        // Check for invalid phone number patterns
                        
                        // 1. Check if all digits are the same (e.g., 9999999999)
                        if (preg_match('/^(\d)\1{9}$/', $value)) {
                            $fail('Phone number cannot have all identical digits.');
                        }
                        
                        // 2. Check for sequential patterns (e.g., 1234567890, 0987654321)
                        $isAscending = true;
                        $isDescending = true;
                        for ($i = 0; $i < 9; $i++) {
                            if ($value[$i + 1] != $value[$i] + 1) {
                                $isAscending = false;
                            }
                            if ($value[$i + 1] != $value[$i] - 1) {
                                $isDescending = false;
                            }
                        }
                        if ($isAscending || $isDescending) {
                            $fail('Phone number cannot be in sequential order.');
                        }
                        
                        // 3. Check for repeating patterns (e.g., 1212121212, 1234123412)
                        if (preg_match('/^(\d{2})\1{4}$/', $value) || // 2-digit pattern repeated 5 times
                            preg_match('/^(\d{3})\1{3}\d$/', $value) || // 3-digit pattern repeated 3+ times
                            preg_match('/^(\d{4})\1{2}\d{2}$/', $value)) { // 4-digit pattern repeated 2+ times
                            $fail('Phone number cannot have repeating patterns.');
                        }
                        
                        // 4. Check for common invalid numbers
                        $invalidNumbers = [
                            '0000000000', '1111111111', '2222222222', '3333333333', '4444444444',
                            '5555555555', '6666666666', '7777777777', '8888888888', '9999999999',
                            '1234567890', '0987654321', '1122334455', '9876543210'
                        ];
                        if (in_array($value, $invalidNumbers)) {
                            $fail('Please enter a valid phone number.');
                        }
                    },
                ],
                'gender'   => 'required|in:Male,Female,Others',
                'password' =>[
                    'required',
                    'string',
                    'min:8',             // must be at least 8 characters in length
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    'regex:/[@$!%*#?&]/', // must contain a special character
                ],
            ];
    
            $validator = Validator::make($rowData, $rules);
    
            if ($validator->fails()) {
                $rowNumber = $index + 2;
                $errorMsg = "Row {$rowNumber} failed validation: " . $validator->errors()->first();
                
                // Add helpful context about the row data
                $rowInfo = [];
                if (!empty($rowData['email'])) $rowInfo[] = "Email: " . $rowData['email'];
                if (!empty($rowData['first_name'])) $rowInfo[] = "Name: " . $rowData['first_name'];
                if (!empty($rowData['phone'])) $rowInfo[] = "Phone: " . $rowData['phone'];
                
                if (!empty($rowInfo)) {
                    $errorMsg .= " (Row contains: " . implode(', ', $rowInfo) . ")";
                }
                
                return redirect()->back()->with('error', $errorMsg);
            }
        }
    
        $results = [
            'users_created' => 0,
            'emails_sent' => 0
        ];

        DB::beginTransaction();
        try {
            foreach ($rows as $rowData) {
                // Check if user already exists globally
                $existingUser = User::where('email', $rowData['email'])->first();
                
                if ($existingUser) {
                    // User exists globally, check if already in this building
                    $buildingUserExists = BuildingUser::where('building_id', Auth::User()->building_id)
                        ->where('user_id', $existingUser->id)
                        ->whereNull('deleted_at')
                        ->exists();
                    
                    if (!$buildingUserExists) {
                        // Add existing user to current building
                        $defaultUserRole = $this->getDefaultUserRole(Auth::User()->building_id);
                        $buildingUser = new BuildingUser();
                        $buildingUser->building_id = Auth::User()->building_id;
                        $buildingUser->user_id = $existingUser->id;
                        $buildingUser->role_id = $defaultUserRole->id;
                        $buildingUser->save();
                        
                        $results['users_created']++;
                    }
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = new User();
                    $user->first_name   = $rowData['first_name'];
                    $user->last_name    = $rowData['last_name'];
                    $user->email        = $rowData['email'];
                    $user->phone        = $rowData['phone'];
                    $user->gender       = $rowData['gender'];
                    $user->password     = Hash::make($rowData['password']);
                    $user->role         = 'user';
                    $user->created_type = $created_type;
                    $user->created_by   = Auth::User()->building_id;
                    $user->status = 'Active';
                    $user->save();
                    
                    // Create BuildingUser relationship
                    $defaultUserRole = $this->getDefaultUserRole(Auth::User()->building_id);
                    $buildingUser = new BuildingUser();
                    $buildingUser->building_id = Auth::User()->building_id;
                    $buildingUser->user_id = $user->id;
                    $buildingUser->role_id = $defaultUserRole->id;
                    $buildingUser->save();
                    
                    $results['users_created']++;
                }
    
                // Send email
                $setting = Setting::first();
                $info = [
                    'user'     => $user,
                    'password' => $rowData['password'],
                    'logo'     => $setting->logo ?? null,
                ];
    
                try {
                    Mail::send('email.password', $info, function ($message) use ($user) {
                        $message->to($user->email, $user->first_name . ' ' . $user->last_name)
                                ->subject('Your Account Details');
                    });
                    $results['emails_sent']++;
                } catch (\Exception $e) {
                    \Log::error("Failed to send email to {$user->email}: " . $e->getMessage());
                }
            }
    
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    
        $message = "Bulk upload completed successfully! ";
        $message .= "Created {$results['users_created']} users";
        if ($results['emails_sent'] > 0) {
            $message .= " and sent {$results['emails_sent']} welcome emails";
        }
        $message .= ".";
        
        return redirect()->back()->with('success', $message);
    }



    public function delete_user(Request $request)
    {
        $user = User::where('id',$request->id)->withTrashed()->first();
        $currentBuildingId = Auth::user()->building_id;
        
        if($request->action == 'delete'){
            // Delete from building_user table for current building
            BuildingUser::where('user_id', $user->id)
                       ->where('building_id', $currentBuildingId)
                       ->delete();
            
            // Check if user exists in other buildings
            $otherBuildingUsers = BuildingUser::where('user_id', $user->id)->count();
            
            // Only soft delete the user if they don't exist in other buildings
            if($otherBuildingUsers == 0) {
                $user->status = 'Inactive';
                $user->save();
                $user->delete();
            }
        }else{
            // Restore user
            $user->status = 'Active';
            $user->save();
            $user->restore();
            
            // Restore building_user relationship if it doesn't exist
            $existingBuildingUser = BuildingUser::withTrashed()
                                               ->where('user_id', $user->id)
                                               ->where('building_id', $currentBuildingId)
                                               ->first();
            
            if($existingBuildingUser) {
                $existingBuildingUser->restore();
            }
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function get_user_by_email(Request $request) {
        $user = User::where('email', $request->email)->orWhere('phone', $request->email)->where('status','Active')->first();
        if ($user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                 'city_id' => $user->city_id,
                'address' => $user->address,
                'gender' => $user->gender,
                'company_name' => $user->company_name,
                'status' => $user->status ?? 'Active',
                'image' => $user->image,
                'image_url' => $user->image ? asset('storage/' . $user->image) : null
            ];
            return response()->json(['success' => true, 'data' => $userData]);
        }
        return response()->json(['success' => false, 'message' => 'User not found']);
    }

    
    public function transactions(Request $request)
    {
        $user = Auth::User();
        if($user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('feature.societyfund'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = $user->building;
        $transactionsQuery = Transaction::where('building_id', $building->id);

        // Filter by model and model_id
        if ($request->filled('model') && $request->model != 'All') {
            $transactionsQuery->where('model', $request->model);

            if ($request->filled('model_id')) {
                $transactionsQuery->where('model_id', $request->model_id);
            }
        }

        // Filter by from_date and to_date
        if ($request->filled('from_date')) {
            $transactionsQuery->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $transactionsQuery->whereDate('date', '<=', $request->to_date);
        }

        $transactions = $transactionsQuery->orderBy('date','desc')->get();
        $building = Auth::User()->building;
        return view('admin.transactions',compact('building','transactions'));
    }
    
    public function orders(Request $request)
    {
        $user = Auth::User();
        if($user->role == 'BA' || $user->hasRole('president') || $user->hasRole('accounts') || $user->hasPermission('feature.societyfund') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = $user->building;
        $orderQuery = Order::where('building_id', $building->id);

        // Filter by model and model_id
        if ($request->filled('model') && $request->model != 'All') {
            $orderQuery->where('model', $request->model);

            if ($request->filled('model_id')) {
                $orderQuery->where('model_id', $request->model_id);
            }
        }

        // Filter by from_date and to_date
        if ($request->filled('from_date')) {
            $orderQuery->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $orderQuery->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $orderQuery->orderBy('created_at','desc')->get();
        
        $building = Auth::User()->building;
        return view('admin.orders',compact('building','orders'));
    }

    public function checkEmailExists(Request $request)
    {
        $email = $request->email;
        $excludeId = $request->exclude_id;
        
        $query = User::where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $exists = $query->exists();
        
        return response()->json(['exists' => $exists]);
    }



    /**
     * Get or create default user role for the building
     */
    private function getDefaultUserRole($buildingId)
    {
        $defaultUserRole = \App\Models\Role::where('building_id', $buildingId)
            ->where('slug', 'user')
            ->first();
        
        if (!$defaultUserRole) {
            // Create default user role if it doesn't exist
            $defaultUserRole = new \App\Models\Role();
            $defaultUserRole->building_id = $buildingId;
            $defaultUserRole->name = 'User';
            $defaultUserRole->slug = 'user';
            $defaultUserRole->type = 'user';
            $defaultUserRole->save();
        }
        
        return $defaultUserRole;
    }
    
    
     public function get_user_guard_info(Request $request) {
        try {
            $user = User::find($request->user_id);
            $currentBuildingId = Auth::user()->building_id;
            
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found']);
            }

            // Get guard data for this user
            $guardData = Guard::with(['building', 'block', 'gate'])
                            ->where('user_id', $user->id)
                            ->get();

            $guards = [];
            foreach ($guardData as $guard) {
                $guards[] = [
                    'id' => $guard->id,
                    'building_id' => $guard->building_id,
                    'building_name' => $guard->building ? $guard->building->name : 'Unknown Building',
                    'block_id' => $guard->block_id,
                    'block_name' => $guard->block ? $guard->block->name : null,
                    'gate_id' => $guard->gate_id,
                    'gate_name' => $guard->gate ? $guard->gate->name : null,
                    'shift' => $guard->shift,
                    'status' => $guard->status,
                    'is_current_building' => $guard->building_id == $currentBuildingId
                ];
            }

            return response()->json([
                'success' => true,
                'guard_data' => $guards
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error loading guard information']);
        }
    }
    
    
       public function checkPhone(Request $request)
    {
        $phone = $request->input('phone');
        $exists = false;
        if ($phone) {
            $exists = \App\Models\User::where('phone', $phone)->exists();
        }
        return response()->json(['exists' => $exists]);
    }
 

}
