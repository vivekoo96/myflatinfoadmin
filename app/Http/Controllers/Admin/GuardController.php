<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guard;
use App\Models\User;
use App\Models\Setting;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use \Hash;
use \Auth;
use \Response;
use \Mail;

class GuardController extends Controller
{
    public function __construct()
    {
        //
    }

    public function index()
    {
        if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" ||  Auth::user()->selectedRole->name == "Security" || Auth::User()->hasPermission('custom.roles') )
        
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;

        // Build listing based on building_users entries for role 'guard'
        $guardRole = $this->getOrCreateGuardRole();
        $guardsData = [];
        if ($guardRole) {
            $buildingUsers = \App\Models\BuildingUser::where('building_id', $building->id)
                ->where('role_id', $guardRole->id)
                ->with('user')
                ->get();

            foreach ($buildingUsers as $bu) {
                $g = Guard::where('user_id', $bu->user_id)
                    ->where('building_id', $building->id)
                    ->withTrashed()
                    ->with(['block','gate'])
                    ->first();

                $guardsData[] = (object) [
                    'building_user' => $bu,
                    'guard' => $g,
                ];
            }
        }

        return view('admin.guard.index', compact('building', 'guardsData'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'building_id' => 'required|exists:buildings,id',
            'block_id' => 'required|exists:blocks,id',
            'gate_id' => 'required|exists:gates,id',
            'user_id'     => [
                'required',
                'exists:users,id',
                Rule::unique('guards')
                    ->ignore($request->id) // allow same user for update
                    ->where(function ($query) {
                        return $query->where('status', 'Active') // only active guards block
                                     ->whereNull('deleted_at');  // only non-deleted guards block
                    }),
            ],
            'gate_id' => 'required|exists:gates,id',
            'shift' => 'required|in:Day,Night',
            'status' => 'required|in:Active,Inactive',
            'company_name' => 'nullable|string|max:40',
            'password' => 'nullable|string|min:6',
        ];
        $messages = [
            'user_id.unique' => 'This user is already a security guard in another place you cant be restore or update anymore',
        ];
        $msg = 'Guard added successfully';
        $guard = new Guard();
    
        if ($request->id) {
            $guard = Guard::withTrashed()->find($request->id);
            $msg = 'Guard Updated';
        }
    
        $validation = \Validator::make($request->all(), $rules,$messages);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        $building = Auth::User()->building;
        $total_other_users = User::where('created_by', Auth::User()->building_id)->where('created_type','other')->withTrashed()->count();
        if($building->no_of_other_users <= $total_other_users){
            return redirect()->back()->with('error', 'Total no of users is '.$building->no_of_other_users.' only');
        }
        $guard->building_id = $request->building_id;
        $guard->block_id = $request->block_id;
        $guard->gate_id = $request->gate_id;
        $guard->user_id = $request->user_id;
        $guard->shift = $request->shift;
        $guard->status = $request->status;
        $guard->save();
        
        // Also save/update in building_users table with guard role
        // Only update guards and building_users status, NOT user status
        $guardRole = $this->getOrCreateGuardRole();
        if ($guardRole && $request->user_id) {
            $buildingUser = \App\Models\BuildingUser::where('building_id', $request->building_id)
                ->where('user_id', $request->user_id)
                ->where('role_id', $guardRole->id)
                ->first();
            
            if (!$buildingUser) {
                $buildingUser = new \App\Models\BuildingUser();
                $buildingUser->building_id = $request->building_id;
                $buildingUser->user_id = $request->user_id;
                $buildingUser->role_id = $guardRole->id;
            }
            // Sync status: Active guard → Active assignment, Inactive guard → Inactive assignment
            $buildingUser->status = $request->status;
            $buildingUser->save();
        }
        
        // Update user's company name and password if provided
        // BUT DO NOT UPDATE USER STATUS - only guards and building_users status should change
        if (($request->company_name || $request->password) && $request->user_id) {
            $user = User::find($request->user_id);
            if ($user) {
                if ($request->company_name) {
                    $user->company_name = $request->company_name;
                }
                if ($request->password) {
                    $user->password = \Hash::make($request->password);
                }
                $user->save();
            }
        }
    
        return redirect()->back()->with('success', $msg);
    }
    
    public function store_new_guard(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        
        // Check if user already exists by email
        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            // User already exists, use this user
            $user = $existingUser;
            $msg = 'Guard added successfully';
            // Update company_name and password for existing user if provided
            if ($request->company_name || $request->password) {
                if ($request->company_name) {
                    $user->company_name = $request->company_name;
                }
                if ($request->password) {
                    $user->password = Hash::make($request->password);
                    // Optionally send password email to existing user
                    try {
                        $setting = Setting::first();
                        $logo = $setting->logo;
                        $info = [
                            'user' => $user,
                            'password' => $request->password,
                            'logo' => $logo,
                        ];
                        Mail::send('email.password', $info, function ($message) use ($user) {
                            $message->to($user->email, $user->name)
                                    ->subject('Account Password Updated');
                        });
                    } catch (\Exception $e) {
                        \Log::warning('Failed to send password email to existing user: ' . $e->getMessage());
                    }
                }
                $user->save();
            }
        } else {
            // User doesn't exist, create new user
            $rules = [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                    Rule::unique('users', 'email'), 
                ],
                'phone' => [
                    'required',
                    'regex:/^[6-9]\d{9}$/', // Exactly 10 digits and starts with 6, 7, 8, or 9
                    Rule::unique('users', 'phone'), 
                ],
                'gender' => 'required|in:Male,Female,Others',
                'password' =>[
                    'nullable',
                    'string',
                    'min:8',             // must be at least 10 characters in length
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    'regex:/[@$!%*#?&]/', // must contain a special character
                ],
                'role' => 'required|in:owner,tanent,user',
                'building_id' => 'required|exists:buildings,id',
                'block_id' => 'required|exists:blocks,id',
                'gate_id' => 'required|exists:gates,id',
                'shift' => 'required|in:Day,Night',
                'status' => 'required|in:Active,Inactive',
            ];
            // Validate the request
            $validation = Validator::make($request->all(), $rules);
        
            if ($validation->fails()) {
                return redirect()->back()->with('error', $validation->errors()->first());
            }
            
            $building = Auth::User()->building;
            $total_other_users = User::where('created_by', Auth::User()->building_id)->where('created_type','other')->withTrashed()->count();
            if($building->no_of_other_users <= $total_other_users){
                return redirect()->back()->with('error', 'Total no of users is '.$building->no_of_other_users.' only');
            }
            
            $user = new User();
            $user->role = $request->role;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->gender = $request->gender;
            $user->company_name = $request->company_name;
            $user->status = 'Active'; // New user always starts with Active status
            // Only set a password if provided
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
        
            $user->created_by = Auth::User()->building_id;
            $user->created_type = 'other';
            $user->save();

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
                                ->subject('Forget Password');
                    });
                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'Failed to queue email. ' . $e->getMessage()
                    ], 500);
                }
            }
            
            $msg = 'Guard added successfully';
        }
        
        $rules_guard = [
            'building_id' => 'required|exists:buildings,id',
            'block_id' => 'required|exists:blocks,id',
            'gate_id' => 'required|exists:gates,id',
            'shift' => 'required|in:Day,Night',
            'status' => 'required|in:Active,Inactive',
        ];
        
        $validation = Validator::make($request->all(), $rules_guard);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        
        $guard = new Guard();
    
        if ($request->id) {
            $guard = Guard::withTrashed()->find($request->id);
        }
    
        $guard->building_id = $request->building_id;
        $guard->block_id = $request->block_id;
        $guard->gate_id = $request->gate_id;
        $guard->user_id = $user->id;
        $guard->shift = $request->shift;
        $guard->status = $request->status;
        $guard->save();
        
        // Also save/update in building_users table with guard role
        $guardRole = $this->getOrCreateGuardRole();
        if ($guardRole) {
            $buildingUser = \App\Models\BuildingUser::where('building_id', $request->building_id)
                ->where('user_id', $user->id)
                ->where('role_id', $guardRole->id)
                ->first();
            
            if (!$buildingUser) {
                $buildingUser = new \App\Models\BuildingUser();
                $buildingUser->building_id = $request->building_id;
                $buildingUser->user_id = $user->id;
                $buildingUser->role_id = $guardRole->id;
            }
            $buildingUser->status = $request->status;
            $buildingUser->save();
        }
    
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security') || Auth::User()->hasRole('president') || Auth::User()->hasPermission('custom.roles') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $guard = Guard::where('id',$id)->where('building_id',Auth::User()->building_id)->withTrashed()->first();
        if(!$guard){
            return redirect()->route('guard.index');
        }
        return view('admin.guard.show',compact('guard'));
    }
    
    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id, Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('security')  )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $guard = Guard::where('id', $id)->withTrashed()->firstOrFail();
    
        if ($request->action == 'delete') {
            // Permanent delete requested: force delete guard and corresponding building_users row
            try {
                // Delete any building_users entry permanently
                $guardRole = $this->getOrCreateGuardRole();
                if ($guardRole) {
                    $buildingUser = \App\Models\BuildingUser::where('building_id', $guard->building_id)
                        ->where('user_id', $guard->user_id)
                        ->where('role_id', $guardRole->id)
                        ->withTrashed()
                        ->first();
                    if ($buildingUser) {
                        // Force delete to permanently remove record
                        $buildingUser->forceDelete();
                    }
                }

                // Permanently remove guard record
                $guard->forceDelete();
            } catch (\Exception $e) {
                \Log::error('Failed to permanently delete guard/building_user: ' . $e->getMessage());
                return response()->json([
                    'msg' => 'error',
                    'detail' => 'Deletion failed'
                ], 500);
            }
        } else {
            // Before restoring, check if user is already active elsewhere
            $exists = Guard::where('user_id', $guard->user_id)
                ->where('status', 'Active')
                ->whereNull('deleted_at')
                ->where('id', '!=', $guard->id) // exclude self
                ->exists();
    
            if ($exists) {
                return response()->json([
                    'msg' => 'This user is already a security guard in another place you cant be restore or update anymore'
                ], 422);
            }
    
            // Restore
            $guard->status = 'Active';
            $guard->save();
            $guard->restore();
            
            // Also restore in building_users
            $guardRole = $this->getOrCreateGuardRole();
            if ($guardRole) {
                $buildingUser = \App\Models\BuildingUser::where('building_id', $guard->building_id)
                    ->where('user_id', $guard->user_id)
                    ->where('role_id', $guardRole->id)
                    ->onlyTrashed()
                    ->first();
                if ($buildingUser) {
                    $buildingUser->restore();
                    $buildingUser->status = 'Active';
                    $buildingUser->save();
                }
            }
        }
    
        return response()->json([
            'msg' => 'success'
        ], 200);
    }

    /**
     * Find existing guard role or create it if missing.
     * Returns Role model instance or null on failure.
     */
    protected function getOrCreateGuardRole()
    {
        $role = \App\Models\Role::whereRaw("LOWER(TRIM(COALESCE(slug, ''))) = ?", ['guard'])->first();
        if ($role) {
            return $role;
        }

        // Try alternative slugs
        $role = \App\Models\Role::whereRaw("LOWER(TRIM(COALESCE(slug, ''))) LIKE ?", ['%guard%'])->first();
        if ($role) {
            return $role;
        }

        // Create a minimal role record if possible
        try {
            $new = new \App\Models\Role();
            if (\Schema::hasColumn('roles', 'name')) {
                $new->name = 'Guard';
            }
            if (\Schema::hasColumn('roles', 'slug')) {
                $new->slug = 'guard';
            }
            $new->save();
            return $new;
        } catch (\Exception $e) {
            // Could not create role (permissions/migration issues). Return null so caller can handle.
            \Log::error('Failed to create guard role: ' . $e->getMessage());
            return null;
        }
    }

}
