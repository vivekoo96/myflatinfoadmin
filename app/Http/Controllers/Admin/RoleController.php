<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\BuildingUser;

use \Auth;
use \DB;
use \Storage;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{

    public function index()
    {
        // dd(Auth::User()->role);
         if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::user()->selectedRole->name == "Issue Tracker" ||  Auth::user()->selectedRole->name == "Security" || Auth::User()->hasPermission('custom.issuestracking') )
      
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        $permissions = Permission::all();
        return view('admin.role.index',compact('building','permissions'));
    }
    
    public function custom_departments()
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.roles') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        // $permissions = $building->permissions->groupBy('group');
        $permissions = Permission::where('guard', 'custom')->get()->groupBy('group');

        if ($building->permissions->contains('name', 'Society fund')) {
            $formsPermission = Permission::where('name', 'Forms')->where('guard', 'custom')->first();
    
            if ($formsPermission) {
                // Add "Forms" into the same group collection
                $permissions[$formsPermission->group][] = $formsPermission;
            }
        }
        return view('admin.role.custom_departments',compact('building','permissions'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if(Auth::User()->role == 'BA' )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            // 'building_id' => 'required|exists:buildings,id',
            'name' => 'required',
            'slug' => 'required',
            'type' => 'required|in:custom,issue',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ];
        $msg = 'Role Added';
        $role = new Role();
        
        if ($request->id) {
            $role = Role::withTrashed()->find($request->id);
            $msg = 'Role Updated';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        $role->building_id = Auth::User()->building_id;
        $role->name = $request->name;
        $role->slug = $request->slug;
        $role->type = $request->type;
        $role->save();
        
        // Sync permissions (detach existing and attach new ones)
        $role->permissions()->sync($request->permissions);
    
        // If request was AJAX, return JSON with the created/updated building_user so client can append row
        if ($request->ajax() || $request->wantsJson()) {
            $building_user->load(['user', 'role', 'user.city']);
            return response()->json([
                'success' => true,
                'message' => $msg,
                'building_user' => $building_user
            ], 200);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.roles') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $role = Role::where('id',$id)->withTrashed()->first();
        if(!$role){
            return redirect()->route('role.index');
        }
        return view('admin.role.show',compact('role'));
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
        if(Auth::User()->role == 'BA' )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $role = Role::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $role->delete();
        }else{
            $role->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function getRolePermissions($id)
    {
        $role = Role::findOrFail($id);
        $permissions = $role->permissions()->pluck('id'); // Get assigned permissions
    
        return response()->json(['permissions' => $permissions]);
    }
    
    public function get_departments($role_slug)
    {
        // dd(Auth::User()->hasRole('president'));
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.roles') || Auth::User()->hasPermission('custom.issuestracking'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $role = Role::where('slug',$role_slug)->first();
        // Get the authenticated user's building_id
        $user = auth()->user();
        $buildingId = $user->building_id;
    
        $building_users = BuildingUser::where('building_id',$buildingId)->where('role_id',$role->id)->withTrashed()->with(['user', 'user.city'])->get();
        $cities = \App\Models\City::all();
        return view('admin.role.department',compact('role','building_users','cities'));
    }
        public function get_department_users($role)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasRole('security') || Auth::User()->hasPermission('custom.roles') ) {
            //
        } else {
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        $user = auth()->user();
        // Use session('current_building_id') if set, else fallback to user's building_id
        $buildingId = session('current_building_id') ?? $user->building_id;
        $building = \App\Models\Building::find($buildingId);
        $building_users = [];
        
        // Get building users filtered by the role parameter
        if ($buildingId && $role) {
            if ($role === 'BA') {
                // Special case: Show current building's BA from users table
                $building_users = \App\Models\User::where('role', 'BA')
                    ->where('building_id', $buildingId)
                    ->with(['city'])
                    ->get();
            } else {
                // Find the role by slug for other roles
                $roleModel = \App\Models\Role::where('slug', $role)->first();
                if ($roleModel) {
                    $building_users = BuildingUser::where('building_id', $buildingId)
                        ->where('role_id', $roleModel->id)
                        ->with(['user', 'user.city'])
                        ->get();
                }
            }
        }
        
        return view('partials.department_users', compact('building', 'building_users'));
    }
    
    
    
    public function store_user_role(Request $request)
    {
        if(Auth::User()->role == 'BA' )
        {
            //
        }else{
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
            }
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        
        $rules = [
            'role_id' => 'required|exists:roles,id',
            'user_id' => 'required|exists:users,id',
            'email' => [
                'nullable',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // Check for suspicious email patterns
                        $domain = explode('@', $value)[1] ?? '';
                        
                        // Reject emails with too many dots in domain (more than 2 levels)
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
                    }
                },
            ],
            'phone' => [
                'nullable',
                'regex:/^[6-9]\d{9}$/',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value) {
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
                    }
                },
            ],
            'gender' => 'nullable|in:Male,Female,Others',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:40',
            'status' => 'required|in:Active,Inactive',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ];
        
        $msg = 'Department Added';
        $building_user = new BuildingUser();
        
        if ($request->id) {
            $building_user = BuildingUser::withTrashed()->find($request->id);
            $msg = 'Department Updated';
        }
    
        $messages = [
            'phone.regex' => 'Please enter a valid phone number (10 digits starting with 6, 7, 8, or 9)',
        ];
        
        $validation = \Validator::make($request->all(), $rules, $messages);
    
        if ($validation->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $validation->errors()->first(), 'errors' => $validation->errors()], 422);
            }
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        
        // Check if user is already assigned to this role in this building (for new assignments only)
        if (!$request->id) {
            $existingAssignment = BuildingUser::where('building_id', Auth::User()->building_id)
                ->where('role_id', $request->role_id)
                ->where('user_id', $request->user_id)
                ->whereNull('deleted_at')
                ->first();
                
            if ($existingAssignment) {
                return redirect()->back()->with('error', 'This user is already assigned to this role in your building.');
            }
        }
        
        $building_user->building_id = Auth::User()->building_id;
        $building_user->role_id = $request->role_id;
        $building_user->user_id = $request->user_id;
        // Persist assignment-level status on building_users (source-of-truth for assignment status)
        if ($request->has('status')) {
            $building_user->status = $request->status;
        }
        $building_user->save();
        Log::info('update_user_role: saved building_user', ['id' => $building_user->id, 'role_id' => $building_user->role_id, 'user_id' => $building_user->user_id, 'building_id' => $building_user->building_id]);
        Log::info('store_user_role: saved building_user', ['id' => $building_user->id, 'role_id' => $building_user->role_id, 'user_id' => $building_user->user_id, 'building_id' => $building_user->building_id]);
        
        // Update User information if provided
        $user = \App\Models\User::find($request->user_id);
        if ($user) {
            if ($request->phone) {
                $user->phone = $request->phone;
            }
            if ($request->gender) {
                $user->gender = $request->gender;
            }
            if ($request->city_id) {
                $user->city_id = $request->city_id;
            }
            if ($request->address) {
                $user->address = $request->address;
            }
            if ($request->company_name) {
                $user->company_name = $request->company_name;
            }
            // NOTE: status is intentionally stored on building_users table per requirements.
            // Do NOT write $user->status here; keep user table status separate if needed.
            
            // Handle image upload
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
            
                $user->save();
            }

            // If request was AJAX, return JSON so the client can append the row
            if ($request->ajax() || $request->wantsJson()) {
                $building_user->load(['user', 'user.city', 'role']);
                Log::info('store_user_role: returning JSON', ['id' => $building_user->id]);
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'building_user' => $building_user
                ], 200);
            }

            return redirect()->back()->with('success', $msg);
    }
    
    public function update_user_role(Request $request)
    {
        if(Auth::User()->role == 'BA' )
        {
            //
        }else{
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
            }
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        
        $rules = [
            'role_id' => 'required|exists:roles,id',
            'user_id' => 'required|exists:users,id',
            'email' => [
                'nullable',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // Check for suspicious email patterns
                        $domain = explode('@', $value)[1] ?? '';
                        
                        // Reject emails with too many dots in domain (more than 2 levels)
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
                    }
                },
            ],
            'phone' => [
                'nullable',
                'regex:/^[6-9]\d{9}$/',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value) {
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
                    }
                },
            ],
            'gender' => 'nullable|in:Male,Female,Others',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:40',
            'status' => 'required|in:Active,Inactive',
            'password' => [
                'nullable',
                'string',
                'min:8',             // must be at least 8 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ];
        
        $messages = [
            'phone.regex' => 'Please enter a valid phone number (10 digits starting with 6, 7, 8, or 9)',
            'password.min' => 'Password must be at least 8 characters long',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character',
        ];
        
        $validation = \Validator::make($request->all(), $rules, $messages);
    
        if ($validation->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $validation->errors()->first(), 'errors' => $validation->errors()], 422);
            }
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        
        // Update BuildingUser relationship
        $building_user = BuildingUser::withTrashed()->find($request->id);
        if (!$building_user) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'User role not found'], 404);
            }
            return redirect()->back()->with('error', 'User role not found');
        }
        
        $building_user->building_id = Auth::User()->building_id;
        $building_user->role_id = $request->role_id;
        $building_user->user_id = $request->user_id;
        // Persist assignment-level status on building_users (source-of-truth for assignment status)
        if ($request->has('status')) {
            $building_user->status = $request->status;
        }
        $building_user->save();
        
        // Update User information
        $user = \App\Models\User::find($request->user_id);
        if ($user) {
            if ($request->first_name) {
                $user->first_name = $request->first_name;
            }
            if ($request->last_name) {
                $user->last_name = $request->last_name;
            }
            if ($request->user_email) {
                $user->email = $request->user_email;
            }
            if ($request->phone) {
                $user->phone = $request->phone;
            }
            if ($request->gender) {
                $user->gender = $request->gender;
            }
            if ($request->city_id) {
                $user->city_id = $request->city_id;
            }
            if ($request->address) {
                $user->address = $request->address;
            }
            if ($request->company_name) {
                $user->company_name = $request->company_name;
            }
            if ($request->password) {
                $user->password = \Hash::make($request->password);
            }
            // NOTE: status is intentionally stored on building_users table per requirements.
            // Do NOT write $user->status here; keep user table status separate if needed.
            
            // Handle image upload
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
            
            $user->save();
        }
    
        if ($request->ajax() || $request->wantsJson()) {
            Log::info('update_user_role: returning JSON', ['id' => $building_user->id]);
            return response()->json(['success' => true, 'message' => 'User information updated successfully', 'building_user' => $building_user], 200);
        }

        return redirect()->back()->with('success', 'User information updated successfully');
    }
    
    public function delete_user_role(Request $request)
    {
        if(Auth::User()->role == 'BA' )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building_user = BuildingUser::where('id',$request->id)->withTrashed()->first();
        if (!$building_user) {
            return response()->json(['success' => false, 'message' => 'Role assignment not found'], 404);
        }

        if ($request->action == 'delete'){
            try {
                // Always perform permanent delete
                $building_user->forceDelete();
                return response()->json(['success' => true, 'message' => 'Permanently deleted'], 200);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Error deleting record'], 500);
            }
        } else {
            $building_user->restore();
            return response()->json(['success' => true, 'message' => 'Restored'], 200);
        }
    }

}
