<?php

namespace App\Http\Controllers\Api;
use App\Jobs\SendForgetPasswordEmail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\User;
use App\Models\Flat;
use App\Models\Noticeboard;
use App\Models\Event;
use App\Models\Classified;
use App\Models\Role;
use App\Models\Issue;
use App\Models\IssuePhoto;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Facility;
use App\Models\Timing;
use App\Models\Booking;
use App\Models\Visitor;
use App\Models\VisitorInout;
use App\Models\FamilyMember;
use App\Models\Guard;
use App\Models\Vehicle;
use App\Models\VehicleInout;
use App\Models\GatePass;
use App\Models\ClassifiedPhoto;
use App\Models\BuildingUser;
use App\Models\Gate;
use App\Models\Ad;
use App\Models\Parcel;
use App\Models\MaintenancePayment;
use App\Models\EssentialPayment;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Essential;
use App\Models\BuildingPermission;
use App\Models\UserDevice;
use App\Models\Expense;
use App\Models\Building;
use App\Models\Notification as DatabaseNotification;
use App\Models\ClassifiedBuilding;
use App\Helpers\AuthHelper;
use App\Helpers\AuthHelperForIssue;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;

use Pushok\Client as ApnsClient;
use Pushok\AuthProvider\Token as ApnsToken;
use Pushok\Notification as ApnsNotification;
use Pushok\Payload;
use Pushok\Payload\Alert;

use App\Helpers\NotificationHelper2 as NotificationHelper;


use Kreait\Firebase\Exception\MessagingException;
use Barryvdh\DomPDF\Facade\Pdf;

use DB;
use \Session;
use Mail;
use \Str;
use \Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestException;

use Illuminate\Support\Arr;


use \Hash;
use \Auth;

class CustomerController extends Controller
{
    public function __construct()
    {
        
        $rdata = Setting::findOrFail(1);
        $this->keyId = $rdata->razorpay_key;
        $this->keySecret = $rdata->razorpay_secret;
        $this->displayCurrency = 'INR';

        $this->api = new Api($this->keyId, $this->keySecret);
        $this->authProvider = ApnsToken::create([
            'key_id' => '4KAVV6FLG4',
            'team_id' => 'XY9Q57Z367',
            'app_bundle_id' => 'com.aits.myflatinfo.dev',
            'private_key_path' => storage_path('app/apns/MyFLATINFO.p8'),
            'private_key_secret' => null,
        ]);
        
        $this->apnsClient = new ApnsClient($this->authProvider, $production = true); // true = production
    }
    
    public function get_setting()
    {
        $setting = Setting::first();
        return response()->json([
            'setting' => $setting
        ],200); 
    }
    
    public function get_treasurer(Request $request)
{
    $user = Auth::user();

    $flats = Flat::where('tanent_id', $user->id)
                 ->orWhere('owner_id', $user->id)
                 ->with('building')
                 ->get();

    $buildings = $flats->pluck('building')->unique('id')->values();

    $treasurerId = $buildings->map(function ($building) {
        return $building->treasurer_id ?? $building->user_id;
    })->filter()->first(); // Only the first ID

    $treasurer = User::find($treasurerId);

    if (!$treasurer) {
        return response()->json(['message' => 'Treasurer not found'], 404);
    }

    return response()->json([
        'treasurers_name' => $treasurer->first_name . ' ' . $treasurer->last_name,
        'treasurers_phone' => $treasurer->phone
    ], 200);
}
    
// Update Thu 20 Nov 01:50
public function get_notifications()
{
    $user = Auth::user();
    
     Log::info('get_notifications'.$user->id.'flat'.AuthHelper::flat()->id);

    $notifications = DatabaseNotification::where('user_id', $user->id)
        ->where('flat_id', AuthHelper::flat()->id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Count unread notifications (where 'read_at' is null)
    $unreadCount = DatabaseNotification::where('user_id', $user->id)
        ->where('flat_id', AuthHelper::flat()->id)
        ->whereNull('read_at')
        ->count();

    return response()->json([
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'iconBadge' => $unreadCount > 0 ? true : false,
        // '$user'=>$user,
        'flat_id'=>AuthHelper::flat()->id,
    ], 200);
}

//06 Nov 2025 6:54
public function get_notifications_department()
{
    $user = Auth::user();
    $department_id=AuthHelperForIssue::department();
    
    $building_users = BuildingUser::where('id', $department_id)->first();

    $notifications = DatabaseNotification::where('user_id', $user->id)
        ->where('role_id', $building_users->role_id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Count unread notifications (where 'read_at' is null)
    $unreadCount = DatabaseNotification::where('user_id', $user->id)
        ->where('role_id', $building_users->role_id)
        ->whereNull('read_at')
        ->count();

    return response()->json([
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'iconBadge' => $unreadCount > 0 ? true : false,
    ], 200);
}

//08 Nov 2025 10:54 AM
public function get_notifications_se(){
        $user = Auth::user();

    $notifications = DatabaseNotification::where('user_id', $user->id)
        // ->where('department_id', $user->department->role_id)
        ->orderBy('created_at', 'desc')
        ->get();

    // Count unread notifications (where 'read_at' is null)
    $unreadCount = DatabaseNotification::where('user_id', $user->id)
        // ->where('department_id', $user->department->role_id)
        ->whereNull('read_at')
        ->count();

    return response()->json([
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'iconBadge' => $unreadCount > 0 ? true : false,
        '$user'=>$user
    ], 200);
    
}




public function get_tokenData()
{
    $user = Auth::user();

    return response()->json([
        'user_tokenData' => $user,
    ], 200);
}

    
    // Controller Method
public function mark_notification_read(Request $request)
{
    // Validate incoming request
    $validated = $request->validate([
        'notification_id' => 'required|integer|exists:notifications,id',
        'mark_admin_read' => 'nullable|boolean',
    ]);

    // Extract variables
    $notificationId = $validated['notification_id'];
    $markAdminRead = $validated['mark_admin_read'] ?? false;

    $notification = DatabaseNotification::where('id', $notificationId)
        ->first();
    if (!$notification) {
        return response()->json([
            'status' => 'error',
            'message' => 'Notification not found'
        ], 404);
    }
    
    
    if (!$notification) {
        return response()->json([
            'status' => 'error',
            'message' => 'Notification not found'
        ], 404);
    }

        $notification->read_at = now();
        $notification->admin_read = $request->mark_admin_read ? 1 : 0;

    $notification->save();

    // Return JSON response
    return response()->json([
        'status' => 'success',
        'message' => 'Notification updated successfully.',
    ], 200);
}

// Batch update method - Mark all as read for user's flat
public function mark_all_notifications_read(Request $request)
{
    $user = Auth::user();

    $updated = DatabaseNotification::where('user_id', $user->id)
        ->where('flat_id', AuthHelper::flat()->id)
        ->whereNull('read_at')
    ->update([
        'read_at' => now(),
        'admin_read' => 1, // set admin_read to 1
        'updated_at' => now(),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'All notifications marked as read',
        'updated_count' => $updated
    ], 200);
}

// Batch update method - Mark all as read for security's flat
public function mark_all_notifications_read_se(Request $request)
{
    $user = Auth::user();

    $updated = DatabaseNotification::where('user_id', $user->id)
        // ->where('flat_id', $user->flat->id)
        ->whereNull('read_at')
    ->update([
        'read_at' => now(),
        'admin_read' => 1, // set admin_read to 1
        'updated_at' => now(),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'All notifications marked as read',
        'updated_count' => $updated
    ], 200);
}

public function clear_all_notifications_user(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized user.',
        ], 401);
    }

    if (!AuthHelper::flat()) {
        return response()->json([
            'status' => 'error',
            'message' => 'User does not have an associated flat.',
        ], 400);
    }

    // Optional filters (for deleting only specific types)
    $validated = $request->validate([
        'screen' => 'nullable|string', // filter by screen from JSON payload
    ]);

    // Build query for user + flat
    $query = DB::table('notifications')
        ->where('user_id', $user->id)
        ->where('flat_id', AuthHelper::flat()->id);

    // Optional: delete notifications only for a specific screen
    if (!empty($validated['screen'])) {
        $query->whereRaw("JSON_EXTRACT(dataPayload, '$.screen') = ?", [$validated['screen']]);
    }

    // Perform permanent deletion
    $deleted = $query->delete();

    return response()->json([
        'status'  => 'success',
        'message' => 'All notifications permanently deleted successfully.',
        'deleted' => $deleted, // number of deleted rows
    ], 200);
}



public function clear_all_notifications_serol(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized user.',
        ], 401);
    }

    // Optional filters (for deleting only specific types)
    $validated = $request->validate([
        'screen' => 'nullable|string', // filter by screen from JSON payload
    ]);

    // Build query for user + flat
    $query = DB::table('notifications')
        ->where('user_id', $user->id);

    // Optional: delete notifications only for a specific screen
    if (!empty($validated['screen'])) {
        $query->whereRaw("JSON_EXTRACT(dataPayload, '$.screen') = ?", [$validated['screen']]);
    }

    // Perform permanent deletion
    $deleted = $query->delete();

    return response()->json([
        'status'  => 'success',
        'message' => 'All notifications permanently deleted successfully.',
        'deleted' => $deleted, // number of deleted rows
    ], 200);
}

    public function user_status()
    {
        $user = Auth::User();
        
        return response()->json([
            'profile_status' => $user->profile_status,
            'status' => $user->status,
        ],200); 
    }
    
    public function register(Request $request)
    {
    
        // Define the validation rules
        $rules = [
            'first_name' => 'required|max:30',
            'last_name' => 'required|max:30',
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
        ];

    
        // Validate the request
        $validation = Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                    'error' => $validation->errors()->first()
                ], 422);
        }
                  
        // Assign user data
        $user = new User();
        // $user->role = 'user';
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->role = 'test';
        $user->desc = $request->desc;
        $user->status = 'Inactive';
    
        $user->created_type = $request->created_type;
        $user->save();

            $setting = Setting::first();
            $logo = $setting->logo;
            $info = array(
                'user' => $user,
                'logo' => $logo,
            );
            // send email
            try {
                Mail::send('email.verification', $info, function ($message) use ($user) {
                    $message->to($user->email, $user->name)
                            ->subject('Account Verification');
                });
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to queue email. ' . $e->getMessage()
                ], 500);
            }
            
        return response()->json([
            'msg' => 'Thank you for registering. We are currently reviewing your account, and you will be notified shortly once the review is complete.'
        ], 200);
                
    }
    
    public function send_otp(Request $request)
    {
        $user = User::where('email',$request->email)->first();
        if($user){
            if($user->status == 'Active'){
                return response()->json([
                    'error' => 'You are already registerd with us, Please login'
                ],422);
            }else{
                $otp = rand(1000,9999);
                $user->otp = Hash::make($otp);
                $user->save();
                //Send email with OTP
                Mail::send([], [], function ($message) use ($user, $otp) {
                    $message->to($user->email)
                        ->subject('Sign up OTP')
                        ->setBody("Your OTP for signup is: $otp", 'text/html');
                });
                return response()->json([
                    'msg' => 'OTP has been sent successfully. ??'
                ],200);
            }
            
            
        }else{
            $user = new User();
        }
        $rules = [
            'email' => 'required|unique:users|email',
        ];
        
        $validation = \Validator::make( $request->all(), $rules );
        $error = $validation->errors()->first();
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ], 422);
        }
        
        $user->role = 'customer';
        $otp = rand(1000,9999);
        $user->email = $request->email;
        $user->status = 'Pending';
        $user->otp = Hash::make($otp);
        $user->otp_status = 'Sent';
        $user->referal_code = 'MFIB'.rand(100000,999999);
        $user->save();
        
        //Send email with OTP
        Mail::send([], [], function ($message) use ($user, $otp) {
            $message->to($user->email)
                ->subject('Sign up OTP')
                ->setBody("Your OTP for signup in Allons-Z is: $otp", 'text/html');
        });
        
            
        return response()->json([
            'msg' => 'OTP has been sent successfully.'
        ],200);
    }
    
    public function resend_otp(Request $request)
    {
        $rules = [
            'email' => 'required|email',
        ];
        $validation = \Validator::make( $request->all(), $rules );
        $error = $validation->errors()->first();
        if($error){
            return response()->json([
                'error' => $error
            ],422);
        }
        
        $user = User::where('email',$request->email)->first();
        if(!$user){
            return response()->json([
                'error' => 'This email is not registered with us'
            ],422);
        }
        
        $otp = rand(1000,9999);
        
        $info = array(
            'user' => Auth::User(),
            'otp' => $otp
        );
        Mail::send([], [], function ($message) use ($user, $otp) {
            $message->to($user->email)
                ->subject('Sign up OTP')
                ->setBody("Your OTP for Allons-Z is: $otp", 'text/html');
        });

        $user->otp = Hash::make($otp);
        $user->otp_status = 'Sent';
        $user->save();

        return response()->json([
            'msg' => 'OTP has been sent successfully.'
        ],200);
    }
    
    public function forget_password(Request $request)
    {
        $rules = [
            'email' => 'required|email|exists:users,email',
        ];
        $validation = \Validator::make( $request->all(), $rules );
        $error = $validation->errors()->first();
        if($error){
            return response()->json([
                'error' => $error
            ],422);
        }
        
        $user = User::where('email',$request->email)->first();
        if(!$user){
            return response()->json([
                'error' => 'This email is not registered with us'
            ],422);
        }
        
        $otp = rand(1000,9999);
        $setting = Setting::first();
        $logo = $setting->logo;
        $info = array(
            'user' => $user,
            'otp' => $otp,
            'logo' => $logo
        );
        try {
            // dispatch(new SendForgetPasswordEmail($user, $info));
            Mail::send('email.forget_password2', $info, function ($message) use ($user) {
                //  $message->to("madipellirohith.123@gmail.com", "Rohith Madipelly")
                $message->to($user->email, $user->name)
                        ->subject('Forgot Password');
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to queue email. ' . $e->getMessage()
            ], 500);
        }

        $user->otp = Hash::make($otp);
        $user->otp_status = 'Sent';
        $user->save();

        return response()->json([
            'msg' => 'OTP has been sent successfully.'
        ],200);
    }
    
    public function verify_otp(Request $request)
    {
        
        $rules = [
            'email' => 'required|email',
            'otp' => 'required|numeric|digits:4',
        ];
        $validation = \Validator::make( $request->all(), $rules );
        $error = $validation->errors()->first();
        if($error){
            return response()->json([
                'error' => $error
            ],422);
        }
        $user = User::where('email',$request->email)->first();
        if($user){
            if (Hash::check($request->otp, $user->otp)) {
                if($user->otp_status == 'Sent'){
                    $user->otp_status = 'Verified';
                    $token = $user->createToken('MyApp')->accessToken;
                    $user->api_token = $token;
                    // $user->device_token = $request->device_token;
                    $user->save();
                    Auth::login($user, true);
                    return response()->json([
                        'token' => $token,
                        'user' => $user,
                        'msg' => 'OTP verified successfully.'
                    ],200);

                }
                return response()->json([
                        'error' => 'This OTP has already been used. Please request a new OTP'
                ],422);
                
            }else{
                return response()->json([
                        'error' => 'Invalid OTP'
                ],422);
            }
        }
        return response()->json([
                'error' => 'Invalid email or OTP.'
        ],422);
    }
    
    public function update_password(Request $request)
    {
        $rules = [
            'password' => [
                'required',
                'string',
                'max:16',
                'min:8',             // must be at least 8 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
                'regex:/^\S*$/',      // Must not contain spaces
                'confirmed',
            ],
        ];

        $validation = \Validator::make( $request->all(), $rules );
        $error = $validation->errors()->first();
        if($error){
            return response()->json([
                'error' => $error
            ],422);
        }
        
        $user = Auth::User();
        
        if($user){
            $user->password = Hash::make($request->password);
            $user->status = 'Active';
            $user->save();
            return response()->json([
                'msg' => 'Password updated.'
            ],200);
        }
        return response()->json([
                'error' => 'User not found.'
        ],422);
    }

     public function login(Request $request)
    {
        $rules = [
            
            'email' => 'required',
            'password' => [
                'required',
            ],
            'fcm_token' => 'nullable|string|max:1000',
            'device_type' => 'nullable|in:android,ios,web',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ];

        $validation = \Validator::make( $request->all(), $rules );
        $error = $validation->errors()->first();
        if($error){
            return response()->json([
                'error' => $error
            ],422);
        }
        
        $user = User::where('email',$request->email)->orWhere('phone',$request->email)->first();
        
        if(!$user){
            return response()->json([
                'error' => 'This account is not register with us'
            ],422);
        }
        
        if ($request->login_type == "security" && $user->id) {
            $isGuard = Guard::where('user_id', $user->id)->first();
            
            if (!$isGuard) {
                return response()->json([
                'error' => 'This account is not registered with us as a security guard'
            ], 422);
        }
        
        }
        
        else if ($request->login_type == "issue") {
    // Get building user records for this user
    $building_users = BuildingUser::where('user_id', $user->id)
        ->get(['id', 'role_id', 'user_id']);

    // Extract role IDs
    $role_ids = $building_users->pluck('role_id');

    // Fetch departments for these role IDs where type is 'issue'
    $departments = Role::whereIn('id', $role_ids)
        ->where('type', 'issue')
        ->get();

    // If no departments found, return error
    if ($departments->isEmpty()) {
        return response()->json([
            'error' => 'This account is not registered with any issue departments.'
        ], 404);
    }
}else if($request->login_type == "user"){
        // Get building user records for this user
    $building_users = BuildingUser::where('user_id', $user->id)
        ->get(['id', 'role_id', 'user_id']);

    // Extract role IDs
    $role_ids = $building_users->pluck('role_id');

    // Fetch departments for these role IDs where type is 'issue'
    $departments = Role::whereIn('id', $role_ids)
        ->where('type', 'user')
        ->get();

    // If no departments found, return error
    if ($departments->isEmpty()) {
        return response()->json([
            'error' => 'This account is not registered with any flats.'
        ], 422);
    }
}

        

        if($user && $user->status == 'Active'){
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('MyApp')->accessToken;
                $user->api_token = $token;
                $user->save();
                Auth::login($user, true);
                
                // Generate unique device ID
                $device_unique_id = UserDevice::generateDeviceUniqueId($request);
                
                // Remove duplicate token if reused
                UserDevice::where('fcm_token', $request->fcm_token)->delete();
                
                // Check if device already exists for this user
                $device = UserDevice::where('user_id', $user->id)
                                   ->where('device_unique_id', $device_unique_id)
                                   ->first();
                
                if (!$device) {
                    $device = new UserDevice();
                    $device->user_id = $user->id;
                    $device->device_unique_id = $device_unique_id;
                    $device->current_flat_id = $user->flat->id ?? null; // Set default flat for new device
                }
                
                // Update device information
                $device->fcm_token     = $request->fcm_token ?? null;
                $device->device_type   = in_array($request->platform, ['android', 'ios', 'web']) ? $request->platform : null;
                $device->device_model  = $request->device_model ?? null;
                $device->device_os     = $request->device_os ?? null;
                $device->app_version   = $request->app_version ?? null;
                $device->lat           = is_numeric($request->lat) ? $request->lat : null;
                $device->lng           = is_numeric($request->lng) ? $request->lng : null;
                $device->ip_address    = $request->ip();
                $device->user_agent    = $request->header('User-Agent') ?? null;
                $device->last_login_at = now();
                $device->app_name = $request->login_type ?? '';
                $device->is_active     = true;
                $device->save();
        // dd($user);
                return response()->json([
                    'token' => $token,
                    'user' => $user,
                    'msg' => 'Login Successfull.'
                ],200);
            }else{
                return response()->json([
                    'error' => 'Invalid Password'
                ],422);
            }
        }else{
            return response()->json([
                'error' => 'This account is Inactive'
            ],422);
        }
    }
    
    public function logout(Request $request)
{
    $rules = [
        'fcm_token' => 'nullable|string|max:1000',
    ];

    $validation = \Validator::make($request->all(), $rules);
    $error = $validation->errors()->first();

    if ($error) {
        return response()->json([
            'error' => $error
        ], 422);
    }

    // Remove the FCM token for this device
    if ($request->fcm_token) {
        UserDevice::where('fcm_token', $request->fcm_token)->delete();
    }

    return response()->json([
        'message' => 'Logged out successfully.'
    ], 200);
}

    
   public function payment_options(Request $request)
    {
        $user = Auth::user();
        
        // Get current flat based on device unique ID
        $device_unique_id = $request->header('Device-Unique-Id');
        if (!$device_unique_id) {
            // Fallback: generate from current request if not provided
            $device_unique_id = UserDevice::generateDeviceUniqueId($request);
        }
        
        $current_flat_id = null;
        $device = UserDevice::where('user_id', $user->id)
                           ->where('device_unique_id', $device_unique_id)
                           ->first();
        
        if($device && $device->current_flat_id) {
            $current_flat_id = $device->current_flat_id;
        }

        $flat=AuthHelper::flat();
        
        $building = Building::where('id',$flat->building_id)->select(['payment_is_active','maintenance_is_active','corpus_is_active','donation_is_active','facility_is_active', 'other_is_active','valid_till','status'])->first();
        $payment_options = $building;
        return response()->json([
                'payment_options' => $payment_options,
                'current_flat' => $flat,
        ],200);
    }
    
   public function profile(Request $request)
    {
        $user = Auth::user();
        
        // Get current flat based on device unique ID
        $device_unique_id = $request->header('Device-Unique-Id');
        if (!$device_unique_id) {
            // Fallback: generate from current request if not provided
            $device_unique_id = UserDevice::generateDeviceUniqueId($request);
        }
    
    $current_flat_id=AuthHelper::flat()->id;
    // 'flat_id'=>AuthHelper::flat()->id
        
        // Get flat - either from device or user default
        if($current_flat_id) {
            $flat = Flat::find($current_flat_id);
        } else {
            $flat = $user->flat;
        }
        
        $block = $flat->block;
        $family_members = $flat->family_members;
        
        // Get vehicles specific to this flat
        $vehicles = \App\Models\Vehicle::where('flat_id', $flat->id)->get();
        
                    
        return response()->json([
            'user' => $user,
            'flat' => $flat,
            'block' => $block,
            'family_members' => $family_members,
            'vehicles' => $vehicles,
        ]);
    }
    
    //User app Optimizationed
      public function profile2(Request $request)
    {
        $user = Auth::user();
        $flat=AuthHelper::flat();
               if (!$flat) {
        return response()->json([
            'error' => 'Flat Id notfounded'
        ], 422);
        }
        $block = $flat->block()->withTrashed()->first();
        $family_members = $flat->family_members()->get();

        // $family_members=FamilyMember::where('flat_id',$flat-id)->get();
        
        $familyCount = FamilyMember::where('flat_id', $flat->id)->count();

       $building = Building::where('id', $flat->building_id)->first();
       

 
 $vehicles = Vehicle::where('flat_id', $flat->id)->get();

$bikeCount = $vehicles->where('vehicle_type', 'two-wheeler')
                      ->where('ownership', 'Own')
                      ->count();

$carCount  = $vehicles->where('vehicle_type', 'four-wheeler')
                      ->where('ownership', 'Own')
                      ->count();

 
 

                    
        return response()->json([
            'user' => $user,
            'flat' => $flat,
            'block' => $block,
            'family_members' => $family_members,
            'familyCount'=>$familyCount,
            'building' => $building,
            'vehicles'=>$vehicles,
            'bike_count'=>$bikeCount,
            'car_count'=>$carCount
        ]);
    }

    
    public function security_profile(Request $request)
    {
        $user = Auth::User();
        $gate = $user->gate;
        $block = $gate->block;
        $building = $block->building;
        $guard = Guard::where('user_id',$user->id)->where('gate_id',$gate->id)->first();
        return response()->json([
            'guard' => $guard,
            'user' => $user,
        ],200);
    }
    
    
    //20nov2025 11:48
    public function update_profile(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => [
                'required',
                'regex:/^[6-9]\d{9}$/', // Exactly 10 digits and starts with 6, 7, 8, or 9
                 'unique:users,phone,' . Auth::id() . ',id',
            ],
            // 'gender' => 'required|in:Male,Female,Other',
            // 'city_id' => 'required|exists:cities,id|numeric',
            // 'address' => 'required|string|min:4',
            // 'pincode' => 'required|numeric|digits:6|',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }

        // if($request->hasFile('photo')) {
        //     $file= $request->file('photo');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($user->getPhotoFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/profiles/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $user->photo = $filename;
        // }
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($user->photo_filename)) {
                $file_path = public_path('images/' . $user->photo_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'profiles/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/profiles/'), $filename);
            $user->photo = $filename;
        }
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        // $user->city_id = $request->city_id;
        // $user->address = $request->address;
        // $user->pincode = $request->pincode;
        $user->save();
    
        return response()->json([
            'msg' => 'Profile updated successfully',
            // 'user' => $user
        ], 200);
    }
    

    


public function update_profile_se(Request $request)
{
    $user = Auth::user();

    $rules = [
        'photo' => 'required|image|mimes:jpeg,jpg,png|max:2048', // max 2MB
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first()
        ], 422);
    }

    if ($request->hasFile('photo')) {
        $file = $request->file('photo');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpeg', 'jpg', 'png'];

        if (!in_array($extension, $allowedExtensions)) {
            return response()->json(['error' => 'Invalid image format.'], 422);
        }

        // Delete old photo if it exists
        if (!empty($user->photo)) {
            $oldFilePath = public_path('images/' . $user->photo);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }

        // Generate clean filename
        $filename = uniqid('profile_') . '.' . $extension;
        $destinationPath = public_path('images/profiles');
        $file->move($destinationPath, $filename);

        // Save relative path only (e.g., "profiles/profile_xxx.jpg")
        $user->photo = 'profiles/' . $filename;
        $user->save();
    }

    // Return full URL correctly
    return response()->json([
        'msg' => 'Profile picture updated successfully',
        'photo_url' => $user->photo
    ], 200);
}


    
    public function change_password(Request $request)
    {
        $user = Auth::user();
        $rules = [
            'old_password' => 'required',
            'password' => [
                'required',
                'string',
                'max:16',
                'min:8',             // must be at least 8 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
                'regex:/^\S*$/',      // Must not contain spaces
                'confirmed',
            ],
            
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        if (Hash::check($request->old_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json([
                'msg' => 'Password updated.'
            ],200);
        }
        
        return response()->json([
                'msg' => 'Invalid old password.'
            ],200);
    }
    
public function get_flats(Request $request)
{
    $user = Auth::user();

    $flats = Flat::where(function ($query) use ($user) {
            $query->where('tanent_id', $user->id)
                  ->orWhere('owner_id', $user->id);
        })
        ->whereNull('deleted_at') // Optional: exclude deleted flats
        ->whereHas('building', function ($query) {
            $query->where('status', 'Active')
                  ->whereNull('deleted_at');
        })
        ->with(['owner', 'tanent', 'block', 'building'])
        ->get();

    return response()->json([
        'flats' => $flats
    ], 200);
}

    
    
public function select_flat(Request $request)
{
    $user = Auth::user();
    $flatId = $request->flat_id;

    // Validate flat_id
    if (!$flatId) {
        return response()->json(['error' => 'flat_id is required'], 422);
    }
    
    $flat = Flat::find($request->flat_id);
    
    
            // Get building user records for this user
    $building_users = BuildingUser::where('user_id', $user->id)
        ->get(['id', 'role_id', 'user_id']);

    // Extract role IDs
    $role_ids = $building_users->pluck('role_id');

    // Fetch departments for these role IDs where type is 'issue'
    $departments = Role::whereIn('id', $role_ids)
        ->where('type', 'user')
        ->get();
        
        
        
    
    // $get_user = BuildingUser::where('user_id', $user->id)
        
    // Get the current access token
    $tokenId = $user->token()->id;

    // Update the token with the selected flat_id
    DB::table('oauth_access_tokens')
        ->where('id', $tokenId)
        ->update(['flat_id' => $flatId]);
        
           if($flat->owner_id == $user->id){
            $role = 'owner';
        }else{
            $role = 'tanent';
        }

    return response()->json([
        'message' => 'Flat selected successfully',
        'flat_id' => $flatId,
        '$tokenId'=>$tokenId,
       'role' => $role,
       'flat' => $flat,
       'user'=>$user,
       'building_id'=>$flat->building_id,
       '$departments'=>$departments
    ], 200);
}



//18-Nov-2025 2:55Pm

    public function get_access(Request $request)
    {
        $user = Auth::User();
        
        $flat = AuthHelper::flat();
        $block = $flat->block;
        $tanent = $flat->tanent;
        $owner = $flat->owner;
        $building = $flat->building;
        $permissions = $building->permissions;
        return response()->json([
            'flat' => $flat,
            'permissions' => $permissions,
        ], 200);
    }
    
    public function get_building_access(Request $request)
    {
        $user = Auth::User();
        $gate = $user->gate;
        $building = $gate->building;
        $permissions = $building->permissions;
        return response()->json([
            'gate' => $gate,
            'permissions' => $permissions,
        ], 200);
    }

    public function get_parkings(Request $request)
    {
        $user = Auth::User();
        $flat = AuthHelper::flat();
        $parkings = $flat->parkings;
        return response()->json([
            'parkings' => $parkings
        ], 200);
    }
    
public function get_ads(Request $request)
{
    $user = Auth::user();
    $flat = AuthHelper::flat();
    $buildingId = $flat->building_id;
    $now = now();

    // Get all ad IDs linked to this building via the ad_buildings pivot table
    $adIds = \App\Models\AdBuilding::where('building_id', $buildingId)->pluck('ad_id');

    $ads = Ad::whereIn('id', $adIds)
        ->where('status', 'Active')
        ->where('from_time', '<=', $now)
        ->where('to_time', '>=', $now)
        ->get();
    
    return response()->json([
        'ads' => $ads,
    ], 200);
}

public function get_overdues(Request $request)
{
        $flat = AuthHelper::flat();
        $maintenance_payments = MaintenancePayment::where('flat_id',$flat->id)->where('status','!=','Paid')->get();
$essential_payments = EssentialPayment::where('flat_id', $flat->id)
    ->where('status', '!=', 'Paid')
    ->whereHas('essential', function ($q) {
        $q->where('status', 'Active');
    })
    ->get();
        
        $corpus_fund = Flat::where('id',$flat->id)->where('is_corpus_paid','No')->first();
        $building = $flat->building;

        $transactions = Transaction::where('building_id', $building->id)->get();

        // Initialize totals
        $total_debit = 0;
        $total_credit = 0;
        $inhand = 0;
        $inbank = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->type == 'Debit') {
                $total_debit += $transaction->amount;
            } elseif ($transaction->type == 'Credit') {
                $total_credit += $transaction->amount;
            }

            // Separate logic for inhand and inbank
            if ($transaction->payment_type == 'InHand') {
                $inhand += ($transaction->type == 'Credit' ? $transaction->amount : -$transaction->amount);
            } elseif ($transaction->payment_type == 'InBank') {
                $inbank += ($transaction->type == 'Credit' ? $transaction->amount : -$transaction->amount);
            }
        }
        
        $unreadCount = DatabaseNotification::where('user_id', Auth::user()->id)
        ->where('flat_id', $flat->id)
        ->whereNull('read_at')
        ->count();



        return response()->json([
     'maintenance_payments_count' => $maintenance_payments->count(),
        'essential_payments_count' => $essential_payments->count(),
                // 'corpus_fund' => $corpus_fund,
                'inbank' => $inbank,
                'inhand' => $inhand,
                 'iconBadge' => $unreadCount > 0 ? true : false,
                //  '$flat'=>$flat
        ],200);
    }
    
public function get_noticeboards(Request $request)
{
    $flat = AuthHelper::flat();
    $block_id = $flat->block_id;
    $currentTime = now(); // Current date and time

    // Validation for pagination, ordering, and search
    $rules = [
        'count' => 'nullable|integer|min:1',
        'page' => 'nullable|integer|min:1',
        'order' => 'nullable|in:asc,desc',
        'search' => 'nullable|string|max:255',
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first(),
        ], 422);
    }

    $query = Noticeboard::where('building_id', $flat->building_id)
        ->where('from_time', '<=', $currentTime)
        ->where('to_time', '>=', $currentTime);
        // ->whereIn('id', function ($q) use ($block_id) {
        //     $q->select('noticeboard_id')
        //       ->from('noticeboard_blocks')
        //       ->where('block_id', $block_id);
        // });

    // Apply search filter if provided
    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where('title', 'like', "%{$search}%");
    }

    // Ordering: default is descending (latest first)
    $order = $request->input('order', 'desc');

    // Pagination
    $count = $request->input('count');
    $page = $request->input('page', 1);

    if ($count) {
        $noticeboards = $query->orderBy('from_time', $order)
                              ->paginate($count, ['*'], 'page', $page);

        return response()->json([
            'noticeboards' => $noticeboards->items(),
            'total' => $noticeboards->total(),
            'current_page' => $noticeboards->currentPage(),
            'last_page' => $noticeboards->lastPage(),
            'block_id' => $block_id,
        ], 200);
    } else {
        $noticeboards = $query->orderBy('from_time', $order)->get();

        return response()->json([
            'noticeboards' => $noticeboards,
            'total' => $noticeboards->count(),
            'current_page' => 1,
            'last_page' => 1,
            'block_id' => $block_id,
        ], 200);
    }
}

// public function get_noticeboards(Request $request)
// {
//     $flat = AuthHelper::flat();
//     $block_id = $flat->block_id;
//     $currentTime = now();

//     // Validate request
//     $rules = [
//         'count' => 'nullable|integer|min:1',
//         'page' => 'nullable|integer|min:1',
//         'order' => 'nullable|in:asc,desc',
//         'search' => 'nullable|string|max:255',
//     ];

//     $validation = Validator::make($request->all(), $rules);
//     if ($validation->fails()) {
//         return response()->json([
//             'error' => $validation->errors()->first(),
//         ], 422);
//     }

//     $query = Noticeboard::where('building_id', $flat->building_id)
//         ->where('from_time', '<=', $currentTime)
//         ->where('to_time', '>=', $currentTime);

//     // Search
//     if ($request->filled('search')) {
//         $search = $request->input('search');
//         $query->where('title', 'like', "%{$search}%");
//     }

//     // Order
//     $order = $request->input('order', 'desc');

//     // Pagination
//     $count = $request->input('count', 10);
//     $page = $request->input('page', 1);

//     // -------------------------------
//     // Fetch all filtered notices first (up to reasonable limit)
//     // -------------------------------
//     $allNotices = $query->orderBy('updated_at', $order)->get();

//     // -------------------------------
//     // GROUP BY DATE (across all notices)
//     // -------------------------------
//     $groups = $allNotices
//         ->groupBy(function ($item) {
//             return Carbon::parse($item->updated_at)->format('Y-m-d');
//         })
//         ->map(function ($notices, $date) {
//             $carbonDate = Carbon::parse($date);
//             return [
//                 'date' => $date,
//                 'label' => $carbonDate->isToday() ? 'Today' :
//                           ($carbonDate->isYesterday() ? 'Yesterday' : $carbonDate->format('d-m-Y')),
//                 'notices' => $notices->values(),
//             ];
//         })
//         ->values();

//     // -------------------------------
//     // Manual Pagination on grouped notices
//     // -------------------------------
//     $totalGroups = $groups->count();
//     $paginatedGroups = $groups->slice(($page - 1) * $count, $count)->values();

//     return response()->json([
//         'groups' => $paginatedGroups,
//         'current_page' => $page,
//         'last_page' => ceil($totalGroups / $count),
//         'total' => $totalGroups,
//         'block_id' => $block_id,
//     ], 200);
// }




//18-Nov-2025 9:50PM
public function get_events(Request $request)
{
        // $flat = Auth::user()->flat;
        $flat=AuthHelper::flat();
        $currentTime = now(); // Get current date and time
    
        $events = Event::where('building_id', $flat->building_id)
            ->where('from_time', '<=', $currentTime)
            ->where('to_time', '>=', $currentTime)
            ->where('status','Active')
            ->with('creator') 
            // ->with('building.user')
            ->orderBy('from_time', 'DESC') 
            // ->orderBy('from_time', $order)
            ->get();
        return response()->json([
                'events' => $events,
        ],200);
    }

public function get_classifieds(Request $request)
{
    $user = Auth::user();
    $flat = AuthHelper::flat();
    $selectedCategory = $request->category;

    if (!$flat) {
        return response()->json(['error' => 'User flat not found.'], 404);
    }

    $buildingId = $flat->building_id;

    // Get all classified IDs linked to this building
    $classifiedIds = ClassifiedBuilding::where('building_id', $buildingId)
        ->pluck('classified_id');

    // Start building the classifieds query
    $classifiedsQuery = Classified::where('status', 'Approved')
    
        ->with([
            'user:id,first_name,last_name,phone,email,photo,role',
            'photos:id,classified_id,photo',
            'flat:id,name',
            'block:id,name',
            'Building:id,name,address'
        ])
         ->orderBy('updated_at', 'asc');

    // Filter based on category
    if ($selectedCategory === 'Within Building') {
        // Show only classifieds linked to this building
        $classifiedsQuery->whereIn('id', $classifiedIds)->where('building_id', $flat->building_id);
    } 
    else {
        // For 'All Buildings', show all approved classifieds
        // Optionally, you can also include classifieds not linked to any building
        // $classifiedsQuery->where('category', 'All Buildings'); // and + where('category', 'Within Building')->where('building_id',$buildingId)
        
             // Show All Buildings posts AND Within Building posts linked to this building
        $classifiedsQuery->where(function ($query) use ($classifiedIds) {
            $query->where('category', 'All Buildings')
                  ->orWhere(function ($q) use ($classifiedIds) {
                      $q->where('category', 'Within Building')
                        ->whereIn('id', $classifiedIds);
                  });
        });
        
    }
    

    $classifieds = $classifiedsQuery->get();

    return response()->json([
        'classifieds' => $classifieds,
        'classifiedIds' => $classifiedIds,
        'selectedCategory' => $selectedCategory
    ]);
}

    
public function my_classifieds(Request $request)
{
    $user = Auth::user();
    $flat = AuthHelper::flat();

    if (!$flat || !$flat->building) {
        return response()->json(['error' => 'Building information not found for your account.'], 404);
    }

    $building = $flat->building;

    // 🔹 Get building limits and durations
    $withinMonths = (int) $building->within_for_month;
    $allMonths = (int) $building->all_for_month;

    $withinStart = now()->copy()->subMonths($withinMonths - 1)->startOfMonth();
    $allStart = now()->copy()->subMonths($allMonths - 1)->startOfMonth();
    $endDate = now()->endOfMonth();

    // 🔹 Fetch user’s classifieds
    $classifieds = Classified::withTrashed()->where('user_id', $user->id)
        ->with([
            'user:id,first_name,last_name,phone,email,photo',
            'photos:id,classified_id,photo',
            'flat',
            'block',
        ])
        ->orderBy('created_at', 'desc')
        ->get();

    // 🔹 Count “Within Building” classifieds in allowed window
    // $withinCount = Classified::where('flat_id', AuthHelper::flat()->id)
    //     ->whereBetween('created_at', [$withinStart, $endDate])
    //     ->where('category', 'Within Building')
    //     ->whereNull('deleted_at')
    //     ->whereNotIn('status', ['Rejected'])
    //     ->count();

    // // 🔹 Count “All Buildings” classifieds in allowed window
    // $allCount = Classified::where('flat_id', AuthHelper::flat()->id)
    //     ->whereBetween('created_at', [$allStart, $endDate])
    //     ->where('category', 'All Buildings')
    //     ->whereNull('deleted_at')
    //     ->whereNotIn('status', ['Rejected'])
    //     ->count();
        
        
        
        $withinCount = Classified::withTrashed()
    ->where('flat_id', AuthHelper::flat()->id)
    ->whereBetween('created_at', [$withinStart, $endDate])
    ->where('category', 'Within Building')
    ->whereNotIn('status', ['Rejected'])
    ->count();


$allCount = Classified::withTrashed()
    ->where('flat_id', AuthHelper::flat()->id)
    ->whereBetween('created_at', [$allStart, $endDate])
    ->where('category', 'All Buildings')
    ->whereNotIn('status', ['Rejected'])
    ->count();


    // 🔹 Calculate remaining limits
    $withinLimit = max(0, $building->classified_limit_within_building - $withinCount);
    $allLimit = max(0, $building->classified_limit_all_building - $allCount);

    // 🔹 Format clean response
    $formatted = $classifieds->map(function ($item) {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'desc' => $item->desc,
            'category' => $item->category,
            'status' => $item->status,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'deleted_at' => $item->deleted_at,
             'reason'=> $item->reason,
            'user' => [
                'first_name' => $item->user->first_name ?? '',
                'last_name' => $item->user->last_name ?? '',
                'phone' => $item->user->phone ?? '',
                'email' => $item->user->email ?? '',
                'photo' => $item->user->photo ?? '',
            ],
            'photos' => $item->photos->pluck('photo'),
            'flat_name' => $item->flat->name ?? $item->flat->flat_no ?? null,
            'block_name' => $item->block->name ?? $item->block->block_name ?? null,
        ];
    });

    return response()->json([
        'classifieds' => $formatted,
        'within_building_count' => $withinCount,
        'all_building_count' => $allCount,
        'within_building_limit' => $withinLimit,
        'all_building_limit' => $allLimit,
        

    ], 200);
}

public function create_classified(Request $request)
{
    $rules = [
        'classified_id' => 'nullable|exists:classifieds,id',
        'title' => 'required|string|max:255',
        'desc' => 'required|string',
        'category' => 'required|in:All Buildings,Within Building',
        'photos' => 'nullable|array',
        'photos.*' => 'image|max:5120',
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json(['error' => $validation->errors()->first()], 422);
    }

    $user = Auth::user();
   $flat = AuthHelper::flat();
    $building = $flat->building;

    if (!$building) {
        return response()->json(['error' => 'Building not found for your account.'], 404);
    }

    // 🔹 Create or update classified
    if ($request->classified_id) {
        $classified = Classified::find($request->classified_id);
        $msg = 'Classified updated successfully';
    } else {
        $classified = new Classified();
        $msg = 'Classified added successfully';
    }

    $classified->fill([
        'building_id' => $flat->building_id,
        'block_id' => $flat->block_id,
        'flat_id' => $flat->id,
        'user_id' => $user->id,
        'title' => $request->title,
        'desc' => $request->desc,
        'category' => $request->category,
        'status' => 'Pending',
        'notification_type' => 'all',
        'is_approved_on_creation' => false,
        'approved_at' => null,
    ])->save();

    // ✅ Handle Classified-Building Mapping
    ClassifiedBuilding::where('classified_id', $classified->id)->delete();

    if ($request->category === 'All Buildings') {
        // Get all building IDs
        $allBuildings = Building::pluck('id')->toArray();

        foreach ($allBuildings as $bId) {
            ClassifiedBuilding::create([
                'classified_id' => $classified->id,
                'building_id' => $bId,
            ]);
        }
    } else {
        // Only the user's own building
        ClassifiedBuilding::create([
            'classified_id' => $classified->id,
            'building_id' => $flat->building_id,
        ]);
    }

    // ✅ Handle photo uploads
    if ($request->hasFile('photos')) {
        foreach ($request->file('photos') as $file) {
            $extension = $file->getClientOriginalExtension();
            $filename = uniqid('classified_') . '.' . $extension;
            $file->move(public_path('/images/classifieds/'), $filename);

            ClassifiedPhoto::create([
                'classified_id' => $classified->id,
                'photo' => $filename,
            ]);
        }
    }

    // 🔔 Optional: Notify user that classified is pending
    // $this->sendUnderReviewNotification($classified);

    return response()->json([
        'msg' => $msg,
        'classified_id' => $classified->id,
    ], 200);
}


public function delete_classified_photo(Request $request)
{
        $rules = [
            'classified_photo_id' => 'required|exists:classified_photos,id',
        ];
        $validation = \Validator::make($request->all(), $rules);
    
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $classified_photo = ClassifiedPhoto::find($request->classified_photo_id);
        Storage::disk('s3')->delete($classified_photo->getPhotoFilenameAttribute());
        $classified_photo->delete();
        return response()->json([
                'msg' => 'Classified photo deleted successfully'
        ],200);
        
    }
    
public function delete_classified(Request $request)
{
        $rules = [
            'classified_id' => [
                'required',
                Rule::exists('classifieds', 'id')->whereNull('deleted_at'),
            ],
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $classified = Classified::find($request->classified_id);
        foreach($classified->photos as $photo){
            Storage::disk('s3')->delete($photo->getPhotoFilenameAttribute());
            $photo->delete();
        }
        $classified->delete();
        return response()->json([
                'msg' => 'Classified deleted successfully'
        ],200);
    }
    

public function get_departments(Request $request)
{
    $flat = AuthHelper::flat();

    // Only get roles where type = 'issue'
    $departments = Role::where('building_id', $flat->building_id)
                        ->where('type', 'issue')
                        ->get();

    return response()->json([
        'departments' => $departments
    ], 200);
}

    
    public function get_staff_directoryxx(Request $request)
    {
    $flat = AuthHelper::flat();
    

    $building_users = BuildingUser::where('building_id', $flat->building_id)
        // ->whereHas('user', function ($query) {
        //     $query->where('created_type', '!=', 'direct');
        // })
        ->with([
            'role:id,name,slug',
            'user:id,first_name,last_name,email,phone,photo,created_type'
        ])
        ->get(['id', 'role_id', 'user_id']);

    return response()->json([
        'staffs' => $building_users,
        'flat_id'=> AuthHelper::flat()->building_id,
    ], 200);
}


public function get_staff_directoryx2(Request $request)
{
    $flat=AuthHelper::flat();
    

    if (!$flat) {
        return response()->json(['error' => 'User does not have a flat assigned.'], 404);
    }

    $building = Building::find($flat->building_id);
    if (!$building) {
        return response()->json(['error' => 'Building not found.'], 404);
    }

    $building_ba_id = $building->user_id;
    $User = User::find($building_ba_id);

    if (!$User) {
        return response()->json(['error' => 'Building admin not found.'], 404);
    }

    // Create BA data as an associative array (not using {})
    $baData = [
        "id" => 1,
        "role_id" => 1,
        "user_id" => $User->id,
        "role" => [
            "id" => 2,
            "name" => "BA",
            "slug" => "accounts"
        ],
        "user" => [
            "id" => $User->id,
            "first_name" => $User->first_name,
            "last_name" => $User->last_name,
            "email" => $User->email,
            "phone" => $User->phone,
            "photo" => $User->photo,
            "created_type" => "other"
        ]
    ];
    

    // Fetch building users
    $building_users = BuildingUser::where('building_id', $flat->building_id)
        ->whereHas('user', function ($query) {
            $query->where('created_type', '!=', 'direct');
        })
        ->with([
            'role:id,name,slug',
            'user:id,first_name,last_name,email,phone,photo,created_type'
        ])
        ->get(['id', 'role_id', 'user_id']);

    // Convert collection to array and append BA data
    // $building_users_array = $building_users->toArray();
    // $building_users_array[] = $baData;
    
    $building_users_array = array_merge($building_users->toArray(), [$baData]);


    return response()->json([
        'staffs' => $building_users_array,
        'flat_id' => $flat->id,
        'building_id' => $flat->building_id,
        'building_ba_id' => $building_ba_id,

    ], 200);
}

public function get_staff_directory(Request $request)
{
    $flat = AuthHelper::flat();

    if (!$flat) {
        return response()->json(['error' => 'User does not have a flat assigned.'], 404);
    }

    $building = Building::find($flat->building_id);
    if (!$building) {
        return response()->json(['error' => 'Building not found.'], 404);
    }

    $building_ba_id = $building->user_id;
    $User = User::find($building_ba_id);

    if (!$User) {
        return response()->json(['error' => 'Building admin not found.'], 404);
    }

    // BA data
    $baData = [
        "id" => 1,
        "role_id" => 1,
        "user_id" => $User->id,
        "role" => [
            "id" => 2,
            "name" => "BA",
            "slug" => "accounts"
        ],
        "user" => [
            "id" => $User->id,
            "first_name" => $User->first_name,
            "last_name" => $User->last_name,
            "email" => $User->email,
            "phone" => $User->phone,
            "photo" => $User->photo,
            "created_type" => "other"
        ]
    ];

    // Fetch building users
    $building_users = BuildingUser::where('building_id', $flat->building_id)
    ->whereHas('role', function ($q) {
        $q->where('slug', '!=', 'user');
    })
        // ->whereHas('user', function ($query) {
        //     $query->where('created_type', '!=', 'direct');
        // })
        ->with([
            'role:id,name,slug',
            'user:id,first_name,last_name,email,phone,photo,created_type'
        ])
        ->get(['id', 'role_id', 'user_id']);

    $building_users_array = array_merge([$baData],$building_users->toArray());

    // Group by role name
    $grouped_staffs = [];
    foreach ($building_users_array as $staff) {
        $roleName = $staff['role']['name'] ?? 'Unknown';
        if (!isset($grouped_staffs[$roleName])) {
            $grouped_staffs[$roleName] = [];
        }
        $grouped_staffs[$roleName][] = $staff;
    }

    return response()->json([
        'staffs' => $grouped_staffs,
        'flat_id' => $flat->id,
        'building_id' => $flat->building_id,
        'building_ba_id' => $building_ba_id,
    ], 200);
}


public function get_flats_directory(Request $request)
{
    $flat = AuthHelper::flat();

    if (!$flat) {
        return response()->json(['error' => 'User does not have a flat assigned.'], 404);
    }
    
    $building = Building::find($flat->building_id);
    if (!$building) {
        return response()->json(['error' => 'Building not found.'], 404);
    }
    
    $flats = Flat::where('building_id',$flat->building_id)
     ->with(['owner', 'tanent', 'block'])
    // ->with([
    //         'owner:id,name,email', 
    //     ])
    ->get();
    // ->get(['name','area','owner:id']);
    
    //     $flats = Building::find($flat->building_id);
    // if (!$building) {
    //     return response()->json(['error' => 'Building not found.'], 404);
    // }
    
    
   return response()->json([
        // 'residents'   => $grouped_residents,
        'flat'     => $flats,
        // 'building' => $building,
    ], 200);
    

    // $building = Building::find($flat->building_id);
    // if (!$building) {
    //     return response()->json(['error' => 'Building not found.'], 404);
    // }

    // $residents = BuildingUser::where('building_id', $flat->building_id)
    //     ->whereHas('role', function ($q) {
    //         $q->where('slug', 'user'); // ✅ correct
    //     })
    //     ->with([
    //         'flat:id,name',
    //         'role:id,name,slug',
    //         'user:id,first_name,last_name,email,phone,photo,created_type'
    //     ])
    //     ->get(['id', 'role_id', 'user_id']);

    // // Group by role name
    // $grouped_residents = [];
    // foreach ($residents as $resident) {
    //     $roleName = $resident->role->name ?? 'Resident';
    //     $grouped_residents[$roleName][] = $resident;
    // }

    // return response()->json([
    //     'residents'   => $grouped_residents,
    //     'flat_id'     => $flat->id,
    //     'building_id' => $flat->building_id,
    // ], 200);
}



//User Issues Step 1
    public function raise_an_issue(Request $request)
    {
        $rules = [
            'department_id' => 'required|exists:roles,id',
            'department_name' => 'required',
            'desc' => 'required',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:2048',
            'periority' => 'required|in:High,Medium,Low',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $flat = AuthHelper::flat();
        $user = Auth::user();
        $issue = new Issue();
        $issue->user_id = $user->id; // ✅ assign the logged-in user
        $issue->building_id = $flat->building_id;
        $issue->role_id = $request->department_id;
        $issue->block_id = $flat->block_id;
        $issue->flat_id = $flat->id;
        $issue->desc = $request->desc;
        $issue->created_by_rolename='User';
        $issue->periority = $request->periority;
        $issue->status = 'Pending';
        $issue->save();

        
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $allowedfileExtension = ['jpeg', 'jpg', 'png'];
                $extension = $file->getClientOriginalExtension();
                $filename = 'issues/' . uniqid() . '.' . $extension;
                $path = $file->move(public_path('/images/issues/'), $filename);

                $photo = new IssuePhoto();
                $photo->issue_id = $issue->id;
                $photo->photo = $filename;
                $photo->save();
            }
        }
        
        $department_Name=$request->department_name;
        
       
        
$building_name=$flat->building->name;
        
$building_users = BuildingUser::where('role_id', $request->department_id)
    ->get(['id', 'role_id', 'user_id']);

// Extract user IDs
$user_ids = $building_users->pluck('user.id')->toArray();

 $title = 'New Issue Raised in '. $building_name .' Building';
 $body =  "A new issue has been reported by ". Auth::user()->name ." in ". $building_name ." Building, for " .$department_Name." department. Please review and take necessary action.";


//  $title = 'New Issue Raised in  Building';
//  $body =  "A new issue has been reported by  in  Building, for  department. Please review and take necessary action.";


        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => "Home",
            'params' => json_encode([
                'ScreenTab' => 'new',
                 'screen' => "home",
                'issue_id' => $issue->id,
                // 'openIssues_comment'=>$comment->id
            ]),
            'categoryId' => 'Issue',
            'channelId' => 'default',
            'sound' => 'default',
            'type' => 'ISSUE_CREATED',
        ];
        

            // Send notification using helper
        $notificationResult = NotificationHelper::sendBulkNotifications(
            $user_ids,
            $title,
            $body,
            $dataPayload,
            [
                'from_id' => $issue->user_id,  // From the person accepting
                // 'array_users_ids' => $user_ids,
                // 'flat_id' => $issue->flat_id,
                'building_id' => $issue->building_id,
                'role_id' =>$request->department_id,
                'type' => 'issue_coming',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'default'
            ],
            ['issue']
        );
    

        
        return response()->json([
                'msg' => 'Issue created successfully',
                '$department_Name'=>$department_Name,
                '$building_name'=>$building_name
        ],200);
    }

//User Issues Step 2 

public function user_issues(Request $request)
{
    $request->validate([
        'status' => 'required|in:Pending,Ongoing,Solved,Completed,Rejected,ComptdRejected,All',
    ]);

    $flat = AuthHelper::flat();

    if (!$flat) {
        return response()->json([
            'status' => 'error',
            'error' => 'User does not have a flat assigned.',
        ], 404);
    }

    $query = Issue::where('building_id', $flat->building_id)
        ->with([
            'department:id,name,type,building_id',
            'photos',
            'user:id,first_name,last_name,photo',
            'flat:id,name,block_id',
            'flat.block',
            'role_user:id,first_name,last_name,photo',
        ])
        ->withCount('comments');

    // FIXED LOGIC
    if ($request->status === 'ComptdRejected') {
        $query->whereIn('status', ['Completed', 'Rejected']);
    } 
    else if ($request->status !== 'All') {
        $query->where('status', $request->status);
    }

    $issues = $query->get();

    return response()->json([
        'status' => 'success',
        'issues' => $issues,
    ]);
}

//User Issues Step 3
  public function add_comment(Request $request)
    {
        $rules = [
            'issue_id' => 'required|exists:issues,id',
            'text' => 'required',
            'type' => 'required', //user//role
        ];
        
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ],422);
        }
        // $issue = Issue::where('id',$request->issue_id)->first();
        $issue = Issue::where('id', $request->issue_id)
              ->with('role_user') // eager load the related user
              ->first();

        $comment = new Comment();
        $comment->issue_id = $request->issue_id;
         $comment->commentedbyRole =$request->type;
    if ($request->type === 'user') {
       
        $comment->user_id = Auth::id(); // use proper column name
    $ScreenTab = $issue->status == 'Onhold' ? 'onhold' : 'ongoing';
        $screen='Home';
        $app_name='issue';
        
    } elseif ($request->type === 'role') {
        $comment->role_user_id = Auth::id();
        $ScreenTab='Pending Issue';
        $screen='Raise an Issue';
         $app_name='user';
    }
    
        $comment->text = $request->text;
        $comment->save();
        
        
        if($request->type==='user'){
            $notify_id =$issue->role_user_id;
            $role_id_based =$issue->role_id;
               $sending='issue';
        }else{
            $notify_id=$issue->user_id;
            $role_id_based =null;
            $sending='user';
        }
    
        
        $commenterName = Auth::user()->name;

        $title = 'New Comment on Your Issue';
        $body = $commenterName . ' commented: ' . $request->text;

        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => $screen,
            'params' => json_encode([
                'screen' => 'home',
                'ScreenTab' => $ScreenTab,
                'issue_id' => $issue->id,
                'issue_status'=>$issue->status,
                'openIssues_comment'=>$comment->id
            ]),
            'categoryId' => 'IssueUpdate',
            'channelId' => 'default',
            'sound' => 'default',
            'type' => 'ISSUE_COMMENT',
            'user_id' => (string)$issue->user_id,
            'flat_id' => (string)$issue->flat_id,
            'building_id' => (string)$issue->building_id,
            'issue_id' => (string)$issue->id,
        ];
        

if($notify_id){
            // Send notification using helper
        $notificationResult = NotificationHelper::sendNotification(
            $notify_id,
            $title,
            $body,
            $dataPayload,
            [
                'from_id' => $notify_id,  // From the person accepting
                'flat_id' => $issue->flat_id,
                'building_id' => $issue->building_id,
                'role_id' => $role_id_based,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'default'
            ],
            [$sending]
        );
}
        return response()->json([
                // 'comment' => $comment,
                'msg' => 'Comment added successfully'.$notify_id?'dsf':'fdsf',
                '$issue->role_user_id'=>$issue->role_id

        ],200);
    }

//User Issues Step 4 (After 3 -> 4 -> 2 again)
    public function get_issue_comments_user(Request $request)
    {
    $LoginedUserId = Auth::user()->id;

    // ✅ 1. Validation
    $rules = [
        'issue_id' => 'required|exists:issues,id',
        'page' => 'sometimes|integer|min:1',
        'count' => 'sometimes|integer|min:1|max:100',
        'sortBy' => 'sometimes|in:asc,desc', // ✅ only allow 'asc' or 'desc'
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json([
            'status' => 'error',
            'error' => $validation->errors()->first(),
        ], 422);
    }

    $issueRaiseUserID = Issue::where('id', $request->issue_id)->value('user_id');

    // ✅ Determine sort order (default: asc)
    $sortBy = $request->sortBy ?? 'asc';

    // ✅ 2. Fetch comments query
    // dd("sedfsdf");
    $query = Comment::where('issue_id', $request->issue_id)
        ->with([
            'user:id,first_name,last_name,photo',
            'role_user:id,first_name,last_name,photo'
        ])
        ->orderBy('created_at', $sortBy);
    


    // ✅ 3. Check if pagination is requested
    if ($request->has('count') || $request->has('page')) {
        $perPage = $request->count ?? 10;
        $comments = $query->paginate($perPage);

        $responseComments = $comments->items();
        $currentPage = $comments->currentPage();
        $lastPage = $comments->lastPage();
        $countPerPage = $comments->perPage();
        $totalCount = $comments->total();
    } else {
        // Return all comments (no pagination)
        $responseComments = $query->get();
        $currentPage = 1;
        $lastPage = 1;
        $countPerPage = $responseComments->count();
        $totalCount = $responseComments->count();
    }

    // ✅ 4. Response
    return response()->json([
        'status' => 'success',
        'issue_id' => $request->issue_id,
        'comments_count' => $totalCount,
        'comments' => $responseComments,
        'current_page' => $currentPage,
        'last_page' => $lastPage,
        'count' => $countPerPage,
        'sortBy' => $sortBy,
        'loginedUserId' => $LoginedUserId,
        'issueRaiseUserID' => $issueRaiseUserID,
    ], 200);
}

    //User Issues Step 5
    public function get_solved_issue(Request $request)
    {
        $flat = AuthHelper::flat();

        $issues = Issue::where('flat_id', $flat->id)->where('status','Solved')->with(['department','comments.replies.user'])->get();
        return response()->json([
                'issues' => $issues
        ],200);
    }

    
    public function add_reply(Request $request)
    {
        $rules = [
            'comment_id' => 'required|exists:comments,id',
            'text' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ],422);
        }
        $comment = Comment::find($request->comment_id);
        if(!$comment){
            return response()->json([
                'status' => 'error',
                'error' => 'Comment not found'
            ],422);
        }
        $issue = Issue::where('id',$comment->issue_id)->where('flat_id',AuthHelper::flat()->flat_id)->first();
        if(!$issue){
            return response()->json([
                'error' => 'Commenting is not enabled for other flat members'
            ],422);
        }
        $reply = new Reply();
        $reply->comment_id = $request->comment_id;
        $reply->user_id = Auth::id();
        $reply->text = $request->text;
        $reply->save();
        return response()->json([
                'reply' => $reply,
                'msg' => 'Reply addedd successfully'
        ],200);
    }
    
    public function get_facilities(Request $request)
    {
        $flat = AuthHelper::flat();
        
        $building = $flat->building;
        $facilities = Facility::where('building_id',$building->id)->where('status','Active')->get();
        return response()->json([
                'facilities' => $facilities
        ],200);
    }
    
    public function get_facility_timings(Request $request)
    {
        $rules = [
            'date' => 'required|date',
            'facility_id' => 'required|exists:facilities,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
    
        $date = Carbon::parse($request->date);
        $facility_id = $request->facility_id;
        $today = Carbon::today();
        $user_id = auth()->id();
    
        $facility = Facility::where('id',$facility_id)->where('status','Active')->first();
        if (!$facility) {
            return response()->json(['status' => 'error', 'error' => 'Facility not found'], 404);
        }
    
        $max_members = $facility->max_booking;
        $perUserMonthlyLimit = $facility->per_user_max_booking;
    
        $allTimings = Timing::where('facility_id', $facility_id)
            ->where('status', 'Active')
            ->get();
    
        if ($allTimings->isEmpty()) {
            return response()->json([
                'remaining_available_booking_for_user' => 0,
                'available_slots' => []
            ], 200);
        }
    
        $startDate = max($date->copy()->startOfMonth(), $today);
        $endDate = $date->copy()->endOfMonth();
    
        // Booked members per timing per date
        $bookedMembersPerTiming = Booking::where('facility_id', $facility_id)
            ->whereIn('status', ['Created', 'Success','Completed'])
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('date', 'timing_id')
            ->selectRaw('date, timing_id, SUM(members) as total_members')
            ->get()
            ->groupBy('date');
    
        // Booked members by this user
        $userBookedMembers = Booking::where('facility_id', $facility_id)
            ->whereIn('status', ['Created', 'Success','Completed'])
            ->where('user_id', $user_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('members');
    
        $remainingForUser = max($perUserMonthlyLimit - $userBookedMembers, 0);
    
        // Process available timings
        $availableData = [];
    
        foreach ($allTimings as $timing) {
            $timingDates = json_decode($timing->dates, true) ?? [];
    
            foreach ($timingDates as $rawDate) {

                if (empty($rawDate)) continue;
    
                try {
                    $timingDate = Carbon::parse($rawDate);
                } catch (\Exception $e) {
                    continue; // skip invalid dates
                }
    
                if ($timingDate->lt($today)) continue;
                if ($timingDate->isToday() && Carbon::parse($timing->from)->lte(Carbon::now())) {
                    continue;
                }
                if ($timingDate->month !== $date->month || $timingDate->year !== $date->year) continue;
                $formattedDate = $timingDate->format('d-m-Y');
                $dailyBookings = $bookedMembersPerTiming[$timingDate->toDateString()] ?? collect();
    
                $bookedMembers = $dailyBookings->firstWhere('timing_id', $timing->id)['total_members'] ?? 0;
                $availableSlots = max($max_members - $bookedMembers, 0);
    
                if ($availableSlots <= 0) continue;
    
                $slotInfo = [
                    'id' => $timing->id,
                    'slotTimeStartToEnd' => Carbon::parse($timing->from)->format('h:i A') . ' to ' . Carbon::parse($timing->to)->format('h:i A'),
                    'available_member_slots' => $availableSlots,
                    'from' => $timing->from,
                    'to' => $timing->to,
                    'price' => $timing->price,
                    'booking_type' => $timing->booking_type,
                ];
    
                // Append or create new
                $existingIndex = collect($availableData)->search(fn($item) => $item['slotDate'] === $formattedDate);
    
                if ($existingIndex !== false) {
                    $availableData[$existingIndex]['slotTimeArray'][] = $slotInfo;
                } else {
                    $availableData[] = [
                        'id' => Str::uuid()->toString(),
                        'slotDate' => $formattedDate,
                        'slotTimeArray' => [$slotInfo],
                    ];
                }
            }
        }
    
        return response()->json([
            'remaining_available_booking_for_user' => $remainingForUser,
            'available_slots' => $availableData
        ], 200);
    }

    // public function book_facility(Request $request)
    // {
    //     $rules = [
    //         'date' => 'required|date',
    //         'facility_id' => 'required|exists:facilities,id',
    //         'timing_ids' => 'required|array',
    //         'timing_ids.*' => 'exists:timings,id',
    //         'members' => 'required|integer|min:1',
    //     ];
    
    //     $validation = \Validator::make($request->all(), $rules);
    
    //     if ($validation->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'error' => $validation->errors()->first()
    //         ], 422);
    //     }
    
    //     $date = $request->date;
    //     $facility_id = $request->facility_id;
    //     $timing_ids = $request->timing_ids; // Array of timing IDs
    //     $members = $request->members;
    //     $user_id = Auth::id();
    
    //     // Get facility's max_booking limit
    //     $facility = Facility::find($facility_id);
    //     $max_members = $facility->max_booking;
    
    //     // Get total booked members per timing
    //     $bookedMembersPerTiming = Booking::where('facility_id', $facility_id)
    //         ->where('date', $date)
    //         ->groupBy('timing_id')
    //         ->selectRaw('timing_id, SUM(members) as total_members')
    //         ->pluck('total_members', 'timing_id')
    //         ->toArray();
    
    //     // Check if there are enough available slots for each requested timing
    //     $unavailableTimings = [];
    //     foreach ($timing_ids as $timing_id) {
    //         $bookedMembers = $bookedMembersPerTiming[$timing_id] ?? 0;
    //         $availableSlots = $max_members - $bookedMembers;
    
    //         if ($members > $availableSlots) {
    //             $unavailableTimings[] = $timing_id;
    //         }
    //     }
    
    //     if (!empty($unavailableTimings)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'error' => 'Not enough available slots for timings: ' . implode(', ', $unavailableTimings)
    //         ], 409); // Conflict status code
    //     }
    
    //     // Create bookings for all available timings
    //     foreach ($timing_ids as $timing_id) {
    //         $booking = new Booking();
    //         $booking->facility_id = $facility_id;
    //         $booking->timing_id = $timing_id;
    //         $booking->date = $date;
    //         $booking->user_id = $user_id;
    //         $booking->members = $members;
    //         $booking->building_id = Auth::User()->flat->building_id;
    //         $booking->status = 'Success';
    //         $booking->save();
    //     }
    
    //     return response()->json([
    //         'status' => 'success',
    //         'msg' => 'All selected timings booked successfully'
    //     ], 200);
    // }
    
    // public function book_facility(Request $request)
    // {
    //     // Validate request input
    //     $rules = [
    //         'date' => 'required|date|after_or_equal:today',
    //         'facility_id' => 'required|exists:facilities,id',
    //         'timings' => 'required|array|min:1',
    //         'timings.*.id' => 'required|exists:timings,id',
    //         'timings.*.members' => 'required|integer|min:1',
    //     ];
    
    //     $validation = \Validator::make($request->all(), $rules);
    
    //     if ($validation->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'error' => $validation->errors()->first()
    //         ], 422);
    //     }
    
    //     // Extract request data
    //     $date = $request->date;
    //     $facility_id = $request->facility_id;
    //     $timings = $request->timings;
    //     $user = Auth::user();
    
    //     // Get active facility
    //     $facility = Facility::where('id', $facility_id)
    //         ->where('status', 'Active')
    //         ->first();
    
    //     if (!$facility) {
    //         return response()->json([
    //             'status' => 'error',
    //             'error' => 'Facility not found or inactive.'
    //         ], 422);
    //     }
    
    //     $max_members = $facility->max_booking;
    //     $monthly_limit = $facility->per_user_max_booking;
    
    //     // Get userâ€™s existing total booked members for current month (same facility)
    //     $monthStart = Carbon::parse($date)->startOfMonth()->toDateString();
    //     $monthEnd = Carbon::parse($date)->endOfMonth()->toDateString();
    
    //     $alreadyBookedMembers = Booking::where('facility_id', $facility_id)
    //         ->where('user_id', $user->id)
    //         ->whereBetween('date', [$monthStart, $monthEnd])
    //         ->sum('members');
    
    //     // Count new requested members
    //     $newRequestedMembers = collect($timings)->sum('members');
    
    //     // Check if total exceeds per-user monthly quota
    //     if (($alreadyBookedMembers + $newRequestedMembers) > $monthly_limit) {
    //         return response()->json([
    //             'status' => 'error',
    //             'error' => "Booking limit exceeded. Already booked: $alreadyBookedMembers, Requested: $newRequestedMembers, Limit: $monthly_limit"
    //         ], 422);
    //     }
    
    //     // Get current booked members per timing for the date
    //     $bookedPerTiming = Booking::where('facility_id', $facility_id)
    //         ->where('date', $date)
    //         ->groupBy('timing_id')
    //         ->selectRaw('timing_id, SUM(members) as total')
    //         ->pluck('total', 'timing_id');
    
    //     $unavailable = [];
    
    //     // Check availability for each requested timing
    //     foreach ($timings as $timing) {
    //         $timing_id = $timing['id'];
    //         $requested_members = $timing['members'];
    
    //         // Ensure timing is active and belongs to this facility
    //         $timingExists = Timing::where('id', $timing_id)
    //             ->where('facility_id', $facility_id)
    //             ->where('status', 'Active')
    //             ->exists();
    
    //         if (!$timingExists) {
    //             $unavailable[] = "Timing ID $timing_id (inactive)";
    //             continue;
    //         }
    
    //         // Check how many members are already booked in this timing
    //         $booked = $bookedPerTiming[$timing_id] ?? 0;
    //         $available = $max_members - $booked;
    
    //         // If requested members exceed available slots, reject
    //         if ($requested_members > $available) {
    //             $unavailable[] = "Timing ID $timing_id (only $available available)";
    //         }
    //     }
    
    //     // If any timing is unavailable, abort booking
    //     if (!empty($unavailable)) {
    //         return response()->json([
    //             'status' => 'error',
    //             'error' => 'Some slots are unavailable: ' . implode(', ', $unavailable)
    //         ], 422);
    //     }
    
    //     // Begin DB transaction to ensure atomic booking
    //     DB::beginTransaction();
    //     try {
    //         foreach ($timings as $timing) {
    //             $booking = new Booking();
    //             $booking->facility_id = $facility_id;
    //             $booking->timing_id = $timing['id'];
    //             $booking->date = $date;
    //             $booking->user_id = $user->id;
    //             $booking->members = $timing['members'];
    //             $booking->building_id = $user->flat->building_id ?? null;
    //             $booking->status = 'Success';
    //             $booking->save();
    //         }
    
    //         DB::commit();
    
    //         return response()->json([
    //             'status' => 'success',
    //             'msg' => 'Facility booked successfully.'
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    
    //         return response()->json([
    //             'status' => 'error',
    //             'error' => 'Booking failed. Please try again.',
    //             'details' => $e->getMessage()
    //         ], 422);
    //     }
    // }

    public function book_facility(Request $request)
    {
        // Validate input structure
        $rules = [
            'facility_id' => 'required|exists:facilities,id',
            'bookings' => 'required|array|min:1',
            'bookings.*.date' => 'required|date|after_or_equal:today',
            'bookings.*.timings' => 'required|array|min:1',
            'bookings.*.timings.*.id' => 'required|exists:timings,id',
            'bookings.*.timings.*.members' => 'required|integer|min:1',
        ];
    
        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $facility_id = $request->facility_id;
        $bookings = $request->bookings;
        $user = Auth::user();
    
        $facility = Facility::where('id', $facility_id)->where('status', 'Active')->first();
        if (!$facility) {
            return response()->json([
                'status' => 'error',
                'error' => 'Facility not found or inactive.'
            ], 422);
        }
    
        $max_members = $facility->max_booking;
        $monthly_limit = $facility->per_user_max_booking;
    
        // Gather all dates
        $allDates = collect($bookings)->pluck('date')->unique();
        $monthStart = Carbon::parse($allDates->first())->startOfMonth();
        $monthEnd = Carbon::parse($allDates->first())->endOfMonth();
    
        $alreadyBookedMembers = Booking::where('facility_id', $facility_id)
            ->whereIn('status', ['Created','Success','Completed'])
            ->where('user_id', $user->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('members');
    
        $newRequestedMembers = collect($bookings)->flatMap(function ($b) {
            return collect($b['timings'])->pluck('members');
        })->sum();
    
        if (($alreadyBookedMembers + $newRequestedMembers) > $monthly_limit) {
            return response()->json([
                'status' => 'error',
                'error' => "Booking limit exceeded. Already booked: $alreadyBookedMembers, Requested: $newRequestedMembers, Limit: $monthly_limit"
            ], 422);
        }
    
        // Validate availability
        $unavailable = [];
    
        foreach ($bookings as $booking) {
            $date = $booking['date'];
            foreach ($booking['timings'] as $timing) {
                $timing_id = $timing['id'];
                $requested_members = $timing['members'];
    
                $timingExists = Timing::where('id', $timing_id)
                    ->where('facility_id', $facility_id)
                    ->where('status', 'Active')
                    ->exists();
    
                if (!$timingExists) {
                    $unavailable[] = "Timing ID $timing_id (inactive)";
                    continue;
                }
                $current_timing = Timing::where('id', $timing_id)->first();
                if($current_timing->price > 0){
                    $unavailable[] = "Timing ID $timing_id (is paid)";
                }
                $booked = Booking::where('facility_id', $facility_id)
                    ->whereIn('status', ['Created','Success','Completed'])
                    ->where('date', $date)
                    ->where('timing_id', $timing_id)
                    ->sum('members');
    
                $available = $max_members - $booked;
    
                if ($requested_members > $available) {
                    $unavailable[] = "Date $date, Timing $timing_id (only $available available)";
                }
            }
        }
    
        if (!empty($unavailable)) {
            return response()->json([
                'status' => 'error',
                'error' => 'Unavailable slots: ' . implode(', ', $unavailable)
            ], 422);
        }
    
        // Save bookings
        DB::beginTransaction();
        try {
            // $transaction = new Transaction();
            // $transaction->building_id = $user->flat->building_id;
            // $transaction->user_id = $user->id;
            // $transaction->order_id = '';
            // $transaction->model = 'Facility';
            // $transaction->model_id = $facility->id;
            // $transaction->type = 'Credit';
            // $transaction->payment_type = 'InBank';
            // $transaction->amount = 0;
            // $transaction->reciept_no = 'RCP'.rand(10000000,99999999);
            // $transaction->desc = 'Maintenance Payment paid by flat number '
            // $transaction->status = 'Success';
            // $transaction->date = now()->toDateString();
            // $transaction->save();
            $reciept = 'RCP'.rand(10000000,99999999);
            foreach ($bookings as $booking) {
                $date = $booking['date'];
                foreach ($booking['timings'] as $timing) {
                    $newBooking = new Booking();
                    $newBooking->facility_id = $facility_id;
                    $newBooking->timing_id = $timing['id'];
                    $newBooking->date = $date;
                    $newBooking->flat_id = AuthHelper::flat()->id;
                    $newBooking->user_id = $user->id;
                    $newBooking->members = $timing['members'];
                    $newBooking->building_id = $user->flat->building_id ?? null;
                    $newBooking->reciept_no = $reciept;
                    $newBooking->status = 'Success';
                    $newBooking->type = 'Offline';
                    $newBooking->save();
                }
            }
    
            DB::commit();
    
            return response()->json([
                'status' => 'success',
                'msg' => 'Facility booked successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'Booking failed.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    
    public function create_facility_order(Request $request)
    {
        // Validate input structure
        $rules = [
            'facility_id' => 'required|exists:facilities,id',
            'bookings' => 'required|array|min:1',
            'bookings.*.date' => 'required|date|after_or_equal:today',
            'bookings.*.timings' => 'required|array|min:1',
            'bookings.*.timings.*.id' => 'required|exists:timings,id',
            'bookings.*.timings.*.members' => 'required|integer|min:1',
        ];
    
        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
    
        $facility_id = $request->facility_id;
        $bookings = $request->bookings;
        $user = Auth::user();
    
        $facility = Facility::where('id', $facility_id)->where('status', 'Active')->first();
        if (!$facility) {
            return response()->json([
                'status' => 'error',
                'error' => 'Facility not found or inactive.'
            ], 422);
        }
    
        $max_members = $facility->max_booking;
        $monthly_limit = $facility->per_user_max_booking;
    
        // Gather all dates
        $allDates = collect($bookings)->pluck('date')->unique();
        $monthStart = Carbon::parse($allDates->first())->startOfMonth();
        $monthEnd = Carbon::parse($allDates->first())->endOfMonth();
    
        $alreadyBookedMembers = Booking::where('facility_id', $facility_id)
            ->whereIn('status', ['Created','Success','Completed'])
            ->where('user_id', $user->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('members');
    
        $newRequestedMembers = collect($bookings)->flatMap(function ($b) {
            return collect($b['timings'])->pluck('members');
        })->sum();
    
        if (($alreadyBookedMembers + $newRequestedMembers) > $monthly_limit) {
            return response()->json([
                'status' => 'error',
                'error' => "Booking limit exceeded. Already booked: $alreadyBookedMembers, Requested: $newRequestedMembers, Limit: $monthly_limit"
            ], 422);
        }
        

        // Validate availability
        $unavailable = [];
    
        foreach ($bookings as $booking) {
            $date = $booking['date'];
            foreach ($booking['timings'] as $timing) {
                $timing_id = $timing['id'];
                $requested_members = $timing['members'];
    
                $timingExists = Timing::where('id', $timing_id)
                    ->where('facility_id', $facility_id)
                    ->where('status', 'Active')
                    ->exists();
    
                if (!$timingExists) {
                    $unavailable[] = "Timing ID $timing_id (inactive)";
                    continue;
                }
    
                $booked = Booking::where('facility_id', $facility_id)
                    ->whereIn('status', ['Created','Success','Completed'])
                    ->where('date', $date)
                    ->where('timing_id', $timing_id)
                    ->sum('members');
    
                $available = $max_members - $booked;
    
                if ($requested_members > $available) {
                    $unavailable[] = "Date $date, Timing $timing_id (only $available available)";
                }
            }
        }
    
        if (!empty($unavailable)) {
            return response()->json([
                'status' => 'error',
                'error' => 'Unavailable slots: ' . implode(', ', $unavailable)
            ], 422);
        }
    
        // Save bookings
        DB::beginTransaction();
        try {
            // $transaction = new Transaction();
            // $transaction->building_id = $user->flat->building_id;
            // $transaction->user_id = $user->id;
            // $transaction->order_id = '';
            // $transaction->model = 'Facility';
            // $transaction->model_id = $facility->id;
            // $transaction->type = 'Credit';
            // $transaction->payment_type = 'InBank';
            // $transaction->amount = 0;
            // $transaction->reciept_no = 'RCP'.rand(10000000,99999999);
            // $transaction->desc = 'Maintenance Payment paid by flat number '.$user->flat->name;
            // $transaction->status = 'Success';
            // $transaction->date = now()->toDateString();
            // $transaction->save();
        $grand_total = 0;
        $refund_amount = 0;
        foreach ($bookings as $booking) {
                $date = $booking['date'];
                foreach ($booking['timings'] as $timing) {
                    $timing_data = Timing::where('id',$timing['id'])->first();
                    $amount = $timing_data->price * $timing['members'];
                    $grand_total += $amount;
                    
                    if($timing_data->cancellation_type == 'Fixed'){
                        $refund_amount += ($timing_data->price - $timing_data->cancellation_value) * $timing['members'];
                    }
                    if($timing_data->cancellation_type == 'Percentage'){
                        $refund_amount = $timing_data->price * $timing_data->cancellation_value / 100;
                        $refund_amount += ($timing_data->price - $refund_amount) * $timing['members'];
                    }
                }
            }

        $refund_amount = ceil($refund_amount);
        $item_amount = $grand_total;
        $gst = $item_amount * $facility->gst / 100;
        $item_amount = $item_amount + $gst;
        $item_amount = ceil($item_amount);
        $orderData = [
            'receipt'         => 'RCP'.rand(10000000,99999999),
            'amount'          => $item_amount * 100, // 2000 rupees in paise
            'currency'        => 'INR',
            'payment_capture' => 1 // auto capture
        ];
               $flat=AuthHelper::flat();
        $building = $flat->building;
        if($building->facility_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        try{
            $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        
            $razorpayOrder = $this->api->order->create($orderData);
        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            $razorpayMessage = $e->getMessage();

            if (strpos($razorpayMessage, 'Amount exceeds maximum amount allowed') !== false) {
                $customMessage = "The amount is too large. Payment not allowed for this amount.";
            } else {
                $customMessage = "We are unable to create a payment. Please contact your building admin.";
            }
        
            return response()->json([
                'error' => $customMessage
            ], 422);
        }
        $razorpayOrderId = $razorpayOrder['id'];
        $displayAmount = $amount = $orderData['amount'];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $url = "https://api.fixer.io/latest?symbols=$this->displayCurrency&base=INR";
            $exchange = json_decode(file_get_contents($url), true);
                    
            $displayAmount = $exchange['rates'][$this->displayCurrency] * $amount / 100;
        }
                    
        $data = [
            "key"               => $building->razorpay_key,
            "amount"            => $amount,
            "name"              => $facility->name,
            "description"       => 'Facility Booking',
            "prefill"           => [
    			"name"              => $user->name,
    			"email"             => $user->email,
    			"contact"           => $user->phone,
            ],
            "notes"             => [
				"address"           => $user->address,
				"merchant_order_id" => $razorpayOrderId,
            ],
            "theme"             => [
				"color"             => "#3399cc"
            ],
            "order_id"          => $razorpayOrderId,
        ];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $data['display_currency']  = $this->displayCurrency;
            $data['display_amount']    = $displayAmount;
        }
                    
        $displayCurrency = $this->displayCurrency;
        
        $order = new Order();
        $order->user_id = $user->id;
        $order->order_id = $razorpayOrderId;
        $order->model = 'Facility';
        $order->model_id = $facility->id;
        $order->building_id = $facility->building_id;
        $order->flat_id = AuthHelper::flat()->id;
        $order->desc = 'Creating order for facility '.$facility->name;
        $order->amount = $item_amount;
        $order->refund_amount = $refund_amount;
        $order->status = 'Created';
        $order->save();
        
            
            foreach ($bookings as $booking) {
                $date = $booking['date'];
                foreach ($booking['timings'] as $timing) {
                    $newBooking = new Booking();
                    $newBooking->facility_id = $facility_id;
                    $newBooking->order_id = $order->id;
                    $newBooking->reciept_no = $orderData['receipt'];
                    $newBooking->timing_id = $timing['id'];
                      $newBooking->flat_id=AuthHelper::flat()->id;
                    $newBooking->date = $date;
                    $newBooking->user_id = $user->id;
                    $newBooking->members = $timing['members'];
                    $newBooking->building_id = AuthHelper::flat()->building_id ?? null;
                    $newBooking->status = 'Created';
                    $newBooking->save();
                }
            }
    
            DB::commit();
            
            // 
            return response()->json([
                'data' => $data,
                'displayCurrency' => $displayCurrency
            ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'Booking failed.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    
    public function verify_facility_order(Request $request)
    {
        $rules = [
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $order = Order::where('order_id',$request->razorpay_order_id)->where('status','Created')->first();
        $order = Order::where('order_id',$request->razorpay_order_id)->first();
        if(!$order){
            return response()->json([
                    'error' => 'Order id not found',
            ],422);
        }
        $success = true;
        $error = "Payment Failed";
        
        $razorpay_order_id = $request->razorpay_order_id;
        $razorpay_payment_id = $request->razorpay_payment_id;
        $razorpay_signature = $request->razorpay_signature;
        
              $flat=AuthHelper::flat();
        $building = $flat->building;
        if($building->facility_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        
        try{
            $attributes = array(
                'razorpay_order_id' => $razorpay_order_id,
                'razorpay_payment_id' => $razorpay_payment_id,
                'razorpay_signature' => $razorpay_signature
            );
        
            $this->api->utility->verifyPaymentSignature($attributes);
            
        }
        catch(SignatureVerificationError $e){
            $success = false;
            $error = 'Razorpay Error : ' . $e->getMessage();
            return response()->json([
                    'error' => $error
            ],400);
        }

        if ($success === true)
        {
            $razorpayOrder = $this->api->order->fetch($razorpay_order_id);
            $reciept = $razorpayOrder['receipt'];
            $transaction_id = $razorpay_payment_id;
            
            $order->payment_id = $razorpay_payment_id;
            $order->signature = $razorpay_signature;
            $order->status = 'Verified';
            $order->save();
            
            $transaction = new Transaction();
            $transaction->building_id = $flat->building_id;
            $transaction->user_id = $user->id;
            $transaction->order_id = $order->order_id;
            $transaction->model = 'Facility';
            $paid_booking = Booking::where('order_id',$order->id)->first();
            $transaction->model_id = $paid_booking->facility_id;
            $transaction->type = 'Credit';
            $transaction->payment_type = 'InBank';
            $transaction->amount = $order->amount;
            $transaction->reciept_no = $reciept;
            $transaction->desc = 'Facility Booking paid by flat number '.$flat->name;
            $transaction->status = 'Success';
            $transaction->date = now()->toDateString();
            $transaction->save();
            


            $bookings = Booking::where('order_id', $order->id)->get();
            
            foreach($bookings as $booking){
                $booking->paid_amount = $booking->payable_amount;
                $booking->payment_type = 'InBank';
                $booking->status = 'Success';
                $booking->transaction_id = $transaction->id;
                $booking->save();
            }
            
            return response()->json([
                    'message' => 'Booking completed! You can now download or view your reciept'
            ],200);

        }
        else
        {
            return response()->json([
                    'error' => $error
            ],422);
        }
    }
    
    public function book_offline_facility(Request $request)
    {
        // Validate input structure
        $rules = [
            'facility_id' => 'required|exists:facilities,id',
            'bookings' => 'required|array|min:1',
            'bookings.*.date' => 'required|date|after_or_equal:today',
            'bookings.*.timings' => 'required|array|min:1',
            'bookings.*.timings.*.id' => 'required|exists:timings,id',
            'bookings.*.timings.*.members' => 'required|integer|min:1',
        ];
    
        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $user = Auth::user();
           $flat=AuthHelper::flat();
        $building = $flat->building;
        $facility_id = $request->facility_id;
        $bookings = $request->bookings;
    
        $facility = Facility::where('id', $facility_id)->where('status', 'Active')->first();
        if (!$facility) {
            return response()->json([
                'status' => 'error',
                'error' => 'Facility not found or inactive.'
            ], 422);
        }
    
        $max_members = $facility->max_booking;
        $monthly_limit = $facility->per_user_max_booking;
    
        // Gather all dates
        $allDates = collect($bookings)->pluck('date')->unique();
        $monthStart = Carbon::parse($allDates->first())->startOfMonth();
        $monthEnd = Carbon::parse($allDates->first())->endOfMonth();
    
        $alreadyBookedMembers = Booking::where('facility_id', $facility_id)
            ->whereIn('status', ['Created','Success','Completed'])
            ->where('user_id', $user->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('members');
    
        $newRequestedMembers = collect($bookings)->flatMap(function ($b) {
            return collect($b['timings'])->pluck('members');
        })->sum();
    
        if (($alreadyBookedMembers + $newRequestedMembers) > $monthly_limit) {
            return response()->json([
                'status' => 'error',
                'error' => "Booking limit exceeded. Already booked: $alreadyBookedMembers, Requested: $newRequestedMembers, Limit: $monthly_limit"
            ], 422);
        }
    
        // Validate availability
        $unavailable = [];
    
        foreach ($bookings as $booking) {
            $date = $booking['date'];
            foreach ($booking['timings'] as $timing) {
                $timing_id = $timing['id'];
                $requested_members = $timing['members'];
    
                $timingExists = Timing::where('id', $timing_id)
                    ->where('facility_id', $facility_id)
                    ->where('status', 'Active')
                    ->exists();
    
                if (!$timingExists) {
                    $unavailable[] = "Timing ID $timing_id (inactive)";
                    continue;
                }
    
                $booked = Booking::where('facility_id', $facility_id)
                    ->whereIn('status', ['Created','Success','Completed'])
                    ->where('date', $date)
                    ->where('timing_id', $timing_id)
                    ->sum('members');
    
                $available = $max_members - $booked;
    
                if ($requested_members > $available) {
                    $unavailable[] = "Date $date, Timing $timing_id (only $available available)";
                }
            }
        }
    
        if (!empty($unavailable)) {
            return response()->json([
                'status' => 'error',
                'error' => 'Unavailable slots: ' . implode(', ', $unavailable)
            ], 422);
        }
    
        // Save bookings
        DB::beginTransaction();
        try {
            // $transaction = new Transaction();
            // $transaction->building_id = $user->flat->building_id;
            // $transaction->user_id = $user->id;
            // $transaction->order_id = '';
            // $transaction->model = 'Facility';
            // $transaction->model_id = $facility->id;
            // $transaction->type = 'Credit';
            // $transaction->payment_type = 'InBank';
            // $transaction->amount = 0;
            // $transaction->reciept_no = 'RCP'.rand(10000000,99999999);
            // $transaction->desc = 'Maintenance Payment paid by flat number '.$user->flat->name;
            // $transaction->status = 'Success';
            // $transaction->date = now()->toDateString();
            // $transaction->save();
        $grand_total = 0;
        $refund_amount = 0;
        foreach ($bookings as $booking) {
                $date = $booking['date'];
                foreach ($booking['timings'] as $timing) {
                    $timing_data = Timing::where('id',$timing['id'])->first();
                    $amount = $timing_data->price * $timing['members'];
                    $grand_total += $amount;
                    
                    if($timing_data->cancellation_type == 'Fixed'){
                        $refund_amount += ($timing_data->price - $timing_data->cancellation_value) * $timing['members'];
                    }
                    if($timing_data->cancellation_type == 'Percentage'){
                        $refund_amount = $timing_data->price * $timing_data->cancellation_value / 100;
                        $refund_amount += ($timing_data->price - $refund_amount) * $timing['members'];
                    }
                }
            }

        $refund_amount = ceil($refund_amount);
        $item_amount = $grand_total;
        $gst = $item_amount * $facility->gst / 100;
        $item_amount = $item_amount + $gst;
        $item_amount = ceil($item_amount);
        
        $reciept_number = 'RCP'.rand(10000000,99999999);
        $order_id = 'ORD'.rand(10000000,99999999);
        
        $order = new Order();
        $order->user_id = $user->id;
        $order->order_id = $order_id;
        $order->model = 'Facility';
        $order->model_id = $facility->id;
        $order->building_id = $facility->building_id;
        $order->flat_id = AuthHelper::flat()->id;
        $order->desc = 'Creating order for offline facility '.$facility->name;
        $order->amount = $item_amount;
        $order->refund_amount = $refund_amount;
        $order->status = 'Created';
        $order->save();
            
            foreach ($bookings as $booking) {
                $date = $booking['date'];
                foreach ($booking['timings'] as $timing) {
                    $newBooking = new Booking();
                    $newBooking->facility_id = $facility_id;
                    $newBooking->order_id = $order->id;
                    $newBooking->reciept_no = $reciept_number;
                    $newBooking->timing_id = $timing['id'];
                    $newBooking->date = $date;
                    $newBooking->user_id = $user->id;
                      $newBooking->flat_id = AuthHelper::flat()->id;
                    $newBooking->members = $timing['members'];
                    $newBooking->building_id = $flat->building_id ?? null;
                    $newBooking->status = 'Created';
                    $newBooking->type = 'Offline';
                    $newBooking->save();
                }
            }
    
            DB::commit();
            
            // 
            return response()->json([
                'msg' => 'Offline booking request created. Waiting for building admin approval.'
            ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error' => 'Booking failed.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // public function cancel_bookings(Request $request)
    // {
    //     $messages = [];
    //     foreach($request->booking_ids as $booking_id){
    //         $booking = Booking::find($booking_id);
            
    //         if($booking->type == 'Online'){
    //             if($booking->status == 'Success'){
    //                 $building = $booking->building;
    //                 $transaction = $booking->transaction;
    //                 $transaction->status = 'Cancelled';
    //                 $transaction->save();
    //                 $refund_amount = $booking->refundable_amount;
    //                 if($booking->timing->cancellation_type == 'Manual'){
    //                     $booking->status = 'Cancel Request';
    //                     $booking->save();
    //                     $messages[] = "Cancel request initiated for booking {$booking->id}. Contact admin.";
    //                     continue;
    //                 }
                    
    //                 if($booking->timing->booking_type == 'Free'){
    //                     $booking->status = 'Cancelled';
    //                     $booking->save();
                        
    //                     $messages[] = "Booking {$booking->id} cancelled successfully (Free).";
    //                     continue;
    //                 }
                
    //                 if($building->facility_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
    //                     return response()->json([
    //                         'error' => 'We are unable to create a payment. Please contact your building admin.'
    //                     ], 422);
    //                 }
                    
    //                 $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
    //                 // Refund through Razorpay
    //                 try {
    //                     $order = $booking->order;
    //                     if ($order->payment_id) {
    //                         $payment = $this->api->payment->fetch($order->payment_id);
                            
    //                         if($refund_amount > 0){
    //                             $refund = $payment->refund([
    //                                 'amount' => $refund_amount * 100, // refund full amount
    //                             ]);
    //                             $order->refund_id = $refund->id;
    //                         }else{
    //                             $refund_amount = 0;
    //                         }
            
    //                         $order->status = 'Refund';
    //                         $order->save();
                            
    //                         $expense = new Expense();
    //                         $expense->user_id = $order->user_id;
    //                         $expense->building_id = $order->building_id;
    //                         $expense->model = $order->model;
    //                         $expense->model_id = $order->model_id;
    //                         $expense->payment_type = 'InBank';
    //                         $expense->reason = 'Booking Cancelled';
    //                         $expense->type = 'Debit';
    //                         $expense->date = now()->toDateString();
    //                         $expense->amount = $refund_amount;
    //                         $expense->save();
                            
    //                         $transaction = new Transaction();
    //                         $transaction->building_id = $order->building_id;
    //                         $transaction->user_id = $order->user_id;
    //                         $transaction->model = $order->model;
    //                         $transaction->model_id = $order->model_id;
    //                         $transaction->date = now()->toDateString();
    //                         $transaction->type = 'Debit';
    //                         $transaction->payment_type = 'InBank';
    //                         $transaction->amount = $refund_amount;
    //                         $transaction->reciept_no = $booking->reciept_no;
    //                         $transaction->desc = 'Booking Cancelled';
    //                         $transaction->status = 'Refund';
    //                         $transaction->save();
                            
    //                     } else {
    //                         return response()->json([
    //                             'error' => "No Razorpay payment ID found for receipt: {$request->reciept_no}"
    //                         ], 422);
    //                     }
    //                 } catch (BadRequestException $e) {
    //                     return response()->json([
    //                         'error' => $e->getMessage()
    //                     ], 422);
    //                 }
    //             }
    //         }else{
    //             if($booking->timing->booking_type == 'Free'){
    //                 $booking->status = 'Cancelled';
    //                 $booking->save();
                    
    //                 $messages[] = "Booking {$booking->id} cancelled successfully (Free).";
    //                 continue;
    //             }
    //             $order = $booking->order;
                
    //             $transaction = $booking->transaction;
    //             if($transaction){
    //                 $transaction->status = 'Cancelled';
    //                 $transaction->save();
    //             }
    //             if($booking->status == 'Created'){
    //                 $booking->status = 'Cancelled';
    //                 $booking->save();
    //                 $messages[] = "Facility booking cancelled successfully.";
    //                 continue;
    //             }
    //             $refund_amount = $booking->refundable_amount;
    //             if($booking->timing->cancellation_type == 'Manual'){
    //                 $booking->status = 'Cancel Request';
    //                 $booking->save();
    //                 $messages[] = "Cancel request initiated for booking {$booking->id}. Contact admin.";
    //                 continue;
    //             }
    //                     $expense = new Expense();
    //                     $expense->user_id = $order->user_id;
    //                     $expense->building_id = $order->building_id;
    //                     $expense->model = $order->model;
    //                     $expense->model_id = $order->model_id;
    //                     $expense->payment_type = 'InHand';
    //                     $expense->reason = 'Booking Cancelled';
    //                     $expense->type = 'Debit';
    //                     $expense->date = now()->toDateString();
    //                     $expense->amount = $refund_amount;
    //                     $expense->save();
                        
    //                     $transaction = new Transaction();
    //                     $transaction->building_id = $order->building_id;
    //                     $transaction->user_id = $order->user_id;
    //                     $transaction->model = $order->model;
    //                     $transaction->model_id = $order->model_id;
    //                     $transaction->date = now()->toDateString();
    //                     $transaction->type = 'Debit';
    //                     $transaction->payment_type = 'InBank';
    //                     $transaction->amount = $refund_amount;
    //                     $transaction->reciept_no = $booking->reciept_no;
    //                     $transaction->desc = 'Booking Cancelled';
    //                     $transaction->status = 'Refund';
    //                     $transaction->save();
    //         }
            
    //         $booking->refunded_amount = $refund_amount;
    //         $booking->status = 'Cancelled';
    //         $booking->save();
    //     }
    
    //     return response()->json([
    //         'msg' => $messages ?: 'Bookings cancelled and refund initiated successfully. Amount will be credited within 5-7 working days.'
    //     ], 200);
    // }
    
    
    
    
     public function cancel_bookings(Request $request)
    {
        \Log::info('cancel_bookings API called', [
            'user_id' => Auth::id(),
            'booking_ids' => $request->booking_ids,
            'request_data' => $request->all()
        ]);
        
        $messages = [];
        foreach($request->booking_ids as $booking_id){
            \Log::info('Processing booking cancellation', ['booking_id' => $booking_id]);
            
            $booking = Booking::find($booking_id);
            if(!$booking){
                \Log::error('Booking not found', ['booking_id' => $booking_id]);
                $messages[] = "Booking {$booking_id} not found.";
                continue;
            }
            
            try {
                \Log::info('Booking found', [
                    'booking_id' => $booking->id,
                    'booking_type' => $booking->type,
                    'booking_status' => $booking->status,
                    'user_id' => $booking->user_id
                ]);
                
                if($booking->type == 'Online'){
                    \Log::info('Processing ONLINE booking', ['booking_id' => $booking->id]);
                    
                    if($booking->status == 'Success'){
                        $building = $booking->building;
                        $transaction = $booking->transaction;
                        
                        if($transaction){
                            $transaction->status = 'Cancelled';
                            $transaction->save();
                            \Log::info('Transaction marked as Cancelled', ['transaction_id' => $transaction->id]);
                        }
                        
                        $refund_amount = $booking->refundable_amount;
                        \Log::info('Refund amount calculated', ['booking_id' => $booking->id, 'refund_amount' => $refund_amount]);
                        
                        if($booking->timing->cancellation_type == 'Manual'){
                            $booking->status = 'Cancel Request';
                            $booking->save();
                            $messages[] = "Cancel request initiated for booking {$booking->id}. Contact admin.";
                            \Log::info('Manual cancellation - status set to Cancel Request', ['booking_id' => $booking->id]);
                            continue;
                        }
                        
                        if($booking->timing->booking_type == 'Free'){
                            $booking->status = 'Cancelled';
                            $booking->save();
                            $messages[] = "Booking {$booking->id} cancelled successfully (Free).";
                            \Log::info('Free booking cancelled', ['booking_id' => $booking->id]);
                            continue;
                        }
                    
                        if($building->facility_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
                            \Log::error('Razorpay keys missing or facility inactive', ['building_id' => $building->id, 'facility_is_active' => $building->facility_is_active]);
                            return response()->json([
                                'error' => 'We are unable to create a payment. Please contact your building admin.'
                            ], 422);
                        }
                        
                        $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
                        \Log::info('Razorpay API initialized', ['building_id' => $building->id]);
                        
                        // Refund through Razorpay
                        try {
                            $order = $booking->order;
                            if ($order && $order->payment_id) {
                                \Log::info('Fetching Razorpay payment', ['payment_id' => $order->payment_id, 'booking_id' => $booking->id]);
                                
                                $payment = $this->api->payment->fetch($order->payment_id);
                                
                                if($refund_amount > 0){
                                    \Log::info('Issuing refund', ['booking_id' => $booking->id, 'refund_amount' => $refund_amount]);
                                    
                                    $refund = $payment->refund([
                                        'amount' => $refund_amount * 100, // refund full amount
                                    ]);
                                    $order->refund_id = $refund->id;
                                    
                                    \Log::info('Refund issued successfully', ['booking_id' => $booking->id, 'refund_id' => $refund->id]);
                                }else{
                                    $refund_amount = 0;
                                    \Log::info('No refund amount', ['booking_id' => $booking->id]);
                                }
            
                                $order->status = 'Refund';
                                $order->save();
                                
                                $expense = new Expense();
                                $expense->user_id = $order->user_id;
                                $expense->building_id = $order->building_id;
                                $expense->model = $order->model;
                                $expense->model_id = $order->model_id;
                                $expense->payment_type = 'InBank';
                                $expense->reason = 'Booking Cancelled for facility';
                                $expense->type = 'Debit';
                                $expense->date = now()->toDateString();
                                $expense->amount = $refund_amount;
                                $expense->save();
                                
                                $transaction = new Transaction();
                                $transaction->building_id = $order->building_id;
                                $transaction->user_id = $order->user_id;
                                $transaction->model = $order->model;
                                $transaction->model_id = $order->model_id;
                                $transaction->date = now()->toDateString();
                                $transaction->type = 'Debit';
                                $transaction->payment_type = 'InBank';
                                $transaction->amount = $refund_amount;
                                $transaction->reciept_no = $booking->reciept_no;
                                $transaction->desc = 'Booking Cancelled for facility';
                                $transaction->status = 'Refund';
                                $transaction->save();
                                
                                \Log::info('Expense and transaction saved', ['booking_id' => $booking->id]);
                            } else {
                                \Log::error('No Razorpay payment ID found', ['booking_id' => $booking->id, 'order_id' => $order ? $order->id : null]);
                                return response()->json([
                                    'error' => "No Razorpay payment ID found for receipt: {$booking->reciept_no}"
                                ], 422);
                            }
                        } catch (BadRequestException $e) {
                            \Log::error('Razorpay refund failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
                            return response()->json([
                                'error' => $e->getMessage()
                            ], 422);
                        }
                    }
                }else{
                    \Log::info('Processing OFFLINE booking', ['booking_id' => $booking->id]);
                    
                    if($booking->timing->booking_type == 'Free'){
                        $booking->status = 'Cancelled';
                        $booking->save();
                        $messages[] = "Booking {$booking->id} cancelled successfully (Free).";
                        \Log::info('Offline free booking cancelled', ['booking_id' => $booking->id]);
                        continue;
                    }
                    
                    $order = $booking->order;
                    $transaction = $booking->transaction;
                    
                    if($transaction){
                        $transaction->status = 'Cancelled';
                        $transaction->save();
                        \Log::info('Transaction marked as Cancelled', ['transaction_id' => $transaction->id]);
                    }
                    
                    if($booking->status == 'Created'){
                        $booking->status = 'Cancelled';
                        $booking->save();
                        $messages[] = "Facility booking cancelled successfully.";
                        \Log::info('Offline created booking cancelled', ['booking_id' => $booking->id]);
                        continue;
                    }
                    
                    $refund_amount = $booking->refundable_amount ?? 0;
                    \Log::info('Refund amount for offline', ['booking_id' => $booking->id, 'refund_amount' => $refund_amount]);
                    
                    if($booking->timing->cancellation_type == 'Manual'){
                        $booking->status = 'Cancel Request';
                        $booking->save();
                        $messages[] = "Cancel request initiated for booking {$booking->id}. Contact admin.";
                        \Log::info('Manual cancellation - status set to Cancel Request', ['booking_id' => $booking->id]);
                        continue;
                    }
                    
                    $expense = new Expense();
                    $expense->user_id = $order->user_id;
                    $expense->building_id = $order->building_id;
                    $expense->model = $order->model;
                    $expense->model_id = $order->model_id;
                    $expense->payment_type = 'InHand';
                    $expense->reason = 'Booking Cancelled for facility';
                    $expense->type = 'Debit';
                    $expense->date = now()->toDateString();
                    $expense->amount = $refund_amount;
                    $expense->save();
                    
                    $transaction = new Transaction();
                    $transaction->building_id = $order->building_id;
                    $transaction->user_id = $order->user_id;
                    $transaction->model = $order->model;
                    $transaction->model_id = $order->model_id;
                    $transaction->date = now()->toDateString();
                    $transaction->type = 'Debit';
                    $transaction->payment_type = 'InBank';
                    $transaction->amount = $refund_amount;
                    $transaction->reciept_no = $booking->reciept_no;
                    $transaction->desc = 'Booking Cancelled for facility';
                    $transaction->status = 'Refund';
                    $transaction->save();
                    
                    \Log::info('Offline expense and transaction saved', ['booking_id' => $booking->id]);
                }
                
                $booking->refunded_amount = $refund_amount ?? 0;
                $booking->status = 'Cancelled';
                $booking->save();
                \Log::info('Booking status updated to Cancelled', ['booking_id' => $booking->id, 'refunded_amount' => $booking->refunded_amount]);
                
            } catch (\Exception $ex) {
                \Log::error('Exception in cancel_bookings', [
                    'booking_id' => $booking_id,
                    'error_message' => $ex->getMessage(),
                    'error_code' => $ex->getCode(),
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine()
                ]);
                $messages[] = "Error cancelling booking {$booking_id}: " . $ex->getMessage();
            }
        }
    
        \Log::info('cancel_bookings completed', [
            'user_id' => Auth::id(),
            'total_bookings' => count($request->booking_ids),
            'messages_count' => count($messages)
        ]);
        
        return response()->json([
            'msg' => $messages ?: 'Bookings cancelled and refund initiated successfully. Amount will be credited within 5-7 working days.'
        ], 200);
    }

   public function my_bookings(Request $request)
{
    $user = Auth::user();
    // $flat = $user->flat;
     $flat =AuthHelper::flat();
    $building = $flat->building;
    $treasurer = $building->treasurer;

    if (!$treasurer) {
        $treasurer = $flat->building->user;
    }

    $bookings = Booking::where('user_id', $user->id)
        ->where('building_id', $flat->building_id)
        ->where('flat_id', $flat->id)
        ->with(['timing','facility','order','transaction'])
        ->get()
        ->groupBy('reciept_no')
        ->map(function ($groupedBookings, $recieptNo) use ($treasurer, $user) {
            $totalBookings = $groupedBookings->count();

            // Count bookings by status
            $statusCount = $groupedBookings->groupBy('status')->map->count();

$statusSummary = collect($statusCount)->map(function ($count, $status) use ($groupedBookings) {
    switch ($status) {
        case 'Created':
            return "$count pending payment";

        case 'Success':
            // If all "Success" bookings have paid_amount == 0, mark as free
            $isFree = $groupedBookings->where('status', 'Success')->every(function ($booking) {
                return $booking->paid_amount == 0;
            });

            return $isFree ? "$count free" : "$count paid";

        case 'Cancelled':
            return "$count cancelled";

        case 'Completed':
            return "$count completed";

        case 'Failed':
            return "$count failed";

        default:
            return "$count $status";
    }
})->implode(', ');



            // Total amounts
            $totalPaid = $groupedBookings->sum('paid_amount');
            $totalRefunded = $groupedBookings->sum('refunded_amount');
            

            // Type check (offline or online)
            $firstType = $groupedBookings->first()->type;
            $status= $groupedBookings->first()->status;

            // Generate description text
            $description = "This receipt includes {$totalBookings} booking(s): {$statusSummary}. ";
            // if ($firstType == 'Offline') {
            //     $description .= "Please pay at the building association office, if not yet paid. ";
            // }
            if ($totalPaid > 0) {
                $description .= "Total paid amount: ₹{$totalPaid}. ";
            }
            if ($totalRefunded > 0) {
                $description .= "Total refunded amount: ₹{$totalRefunded}.";
            }
            
            
            
 

// --- Count payment types ---
$inBank = $groupedBookings->where('payment_type', 'InBank')->count();
$inHand = $groupedBookings->where('payment_type', 'InHand')->count();

// --- Check free booking conditions ---
// All bookings free?
$allFree = $groupedBookings->every(function ($booking) {
    return $booking->paid_amount == 0 && $booking->timing->booking_type == 'Free';
});

// Any booking that requires payment?
$anyPaidBookingType = $groupedBookings->contains(function ($booking) {
    return $booking->timing->booking_type == 'Paid';
});

// Any actual payment made?
$anyPaidAmount = $groupedBookings->contains(function ($booking) {
    return $booking->paid_amount > 0;
});


// --- Determine Payment Mode ---
// Case 1: All bookings are free → No payment needed
if ($allFree) {
    $paymentMode = 'Free booking - no payment needed';

// Case 2: Some bookings require payment BUT no payment done yet
} elseif ($anyPaidBookingType && !$anyPaidAmount) {
    $paymentMode = 'Not yet paid';

// Case 3: Some payments made → determine how
} else {

    if ($inBank > 0 && $inHand === 0) {

        $paymentMode = "All paid In Bank ({$inBank})";

    } elseif ($inHand > 0 && $inBank === 0) {

        $paymentMode = "All paid In Cash ({$inHand})";

    } else {

        $paymentMode = "Mixed payment types (InBank: {$inBank}, In Cash: {$inHand})";

    }
}


            return [
                'reciept_no' => $recieptNo,
                'treasurer_name' => $treasurer->name,
                'treasurer_phone' => $treasurer->phone,
                'bookings' => $groupedBookings,
                'payment_mode' => $paymentMode, // ⬅️ Added
                'user' => $user->only(['id', 'name']),
                'description' => trim($description),
            ];
        })
        ->values();

    return response()->json([
        'bookings' => $bookings,
        'flat_info' => [
            'flat_name' => $flat->name,
            'building_name' => $flat->building->name ?? null,
            'block_name' => $flat->block->name ?? null,
        ]
    ], 200);
}



public function get_bookings_by_reciept(Request $request)
{
    $user = Auth::user();
    // $flat = $user->flat;

     $flat =AuthHelper::flat();
    $building = $flat->building;
    $treasurer = $building->treasurer;

    if (!$treasurer) {
        $treasurer = $flat->building->user;
    }

    $bookings = Booking::where('user_id', $user->id)
        ->where('building_id', $flat->building_id)
        ->where('reciept_no', $request->reciept_no)
        ->with(['timing', 'facility', 'order', 'transaction'])
        ->get()
        ->groupBy('reciept_no')
        ->map(function ($groupedBookings, $recieptNo) use ($treasurer,$user) {
            $totalBookings = $groupedBookings->count();

            // Count by status
            $statusCount = $groupedBookings->groupBy('status')->map->count();

$statusSummary = collect($statusCount)->map(function ($count, $status) use ($groupedBookings) {
    switch ($status) {
        case 'Created':
            return "$count pending payment";

        case 'Success':
            // If all "Success" bookings have paid_amount == 0, mark as free
            $isFree = $groupedBookings->where('status', 'Success')->every(function ($booking) {
                return $booking->paid_amount == 0;
            });

            return $isFree ? "$count free" : "$count paid";

        case 'Cancelled':
            return "$count cancelled";

        case 'Completed':
            return "$count completed";

        case 'Failed':
            return "$count failed";

        default:
            return "$count $status";
    }
})->implode(', ');



            // Total amounts
            $totalPaid = $groupedBookings->sum('paid_amount');
            $totalRefunded = $groupedBookings->sum('refunded_amount');
            



            // Type check (offline or online)
            $firstType = $groupedBookings->first()->type;
            $status= $groupedBookings->first()->status;

            // Generate description text
            $description = "This receipt includes {$totalBookings} booking(s): {$statusSummary}. ";
            // if ($firstType == 'Offline') {
            //     $description .= "Please pay at the building association office, if not yet paid. ";
            // }
            if ($totalPaid > 0) {
                $description .= "Total paid amount: ₹{$totalPaid}. ";
            }
            if ($totalRefunded > 0) {
                $description .= "Total refunded amount: ₹{$totalRefunded}.";
            }




// --- Count payment types ---
$inBank = $groupedBookings->where('payment_type', 'InBank')->count();
$inHand = $groupedBookings->where('payment_type', 'InHand')->count();

// --- Check free booking conditions ---
// All bookings free?
$allFree = $groupedBookings->every(function ($booking) {
    return $booking->paid_amount == 0 && $booking->timing->booking_type == 'Free';
});

// Any booking that requires payment?
$anyPaidBookingType = $groupedBookings->contains(function ($booking) {
    return $booking->timing->booking_type == 'Paid';
});

// Any actual payment made?
$anyPaidAmount = $groupedBookings->contains(function ($booking) {
    return $booking->paid_amount > 0;
});


// --- Determine Payment Mode ---
// Case 1: All bookings are free → No payment needed
if ($allFree) {
    $paymentMode = 'Free booking - no payment needed';

// Case 2: Some bookings require payment BUT no payment done yet
} elseif ($anyPaidBookingType && !$anyPaidAmount) {
    $paymentMode = 'Not yet paid';

// Case 3: Some payments made → determine how
} else {

    if ($inBank > 0 && $inHand === 0) {

        $paymentMode = "All paid In Bank ({$inBank})";

    } elseif ($inHand > 0 && $inBank === 0) {

        $paymentMode = "All paid In Cash ({$inHand})";

    } else {

        $paymentMode = "Mixed payment types (InBank: {$inBank}, In Cash: {$inHand})";

    }
}





            return [
                'payment_mode' => $paymentMode, // ⬅️ Added
                'description' => trim($description),
                'bookings' => $groupedBookings,
                'reciept_no' => $recieptNo,
                'treasurer_name' => $treasurer->name,
                'treasurer_phone' => $treasurer->phone,
                'user' => $user->only(['id', 'name']),
            ];
        })
        ->values();

    return response()->json([
        'bookings' => $bookings,
        'flat_info' => [
            'flat_name' => $flat->name,
            'building_name' => $flat->building->name ?? null,
            'block_name' => $flat->block->name ?? null,
        ],
    ], 200);
}


    public function get_bookings_by_recieptxxxxx(Request $request)
    {
        $user = Auth::user();
        // $flat = $user->flat;
                $flat=AuthHelper::flat();
        $building = $flat->building;
        $treasurer = $building->treasurer;
        if(!$treasurer){
            $treasurer = $flat->building->user;
        }
        $bookings = Booking::where('user_id', $user->id)
            ->where('building_id', $flat->building_id)
            ->where('reciept_no',$request->reciept_no)
            ->with(['timing','facility','order','transaction'])
            ->get()
            ->groupBy('reciept_no')
            ->map(function ($groupedBookings, $recieptNo) use ($treasurer) {
                return [
                    'reciept_no' => $recieptNo,
                    'treasurer_name' => $treasurer->name,
                    'treasurer_phone' => $treasurer->phone,
                    'bookings' => $groupedBookings,
                ];
            })
            ->values();
    
        return response()->json([
            'bookings' => $bookings,
        ], 200);
    }

    public function create_visitor(Request $request)
    {
        $rules = [
            'total_members' => 'required|integer|min:1',
            'head_name' => 'required',
            'head_phone' => 'required',
            'head_photo' => 'nullable|image|max:5120',
            'stay_from' => 'required',
            'stay_to' => 'required',
            'visiting_purpose' => 'required',
            'type' => 'required|in:Planned,Unplanned',
            'stay_option' => 'required|in:Daily,Hourly',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        
        $user = Auth::User();
        $flat = AuthHelper::flat();
        if($request->flat_id){
            $flat = Flat::find($request->flat_id);
        }
        if($request->visitor_id){
            $visitor = Visitor::find($request->visitor_id);
        }else{
            $visitor = new Visitor();
            $code = mt_rand(100000, 999999);
            $visitor->code = $code;
        }

        
        if ($request->hasFile('head_photo')) {
            $file = $request->file('head_photo');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($visitor->head_photo_filename)) {
                $file_path = public_path('images/' . $visitor->head_photo_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'visitors/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/visitors/'), $filename);
            $visitor->head_photo = $filename;
        }
        
        
        // Before update
$oldStayTo = $visitor->stay_to;

        $visitor->user_id = $user->id;
        $visitor->building_id = $flat->building_id;
        $visitor->flat_id = $flat->id;
        $visitor->block_id = $flat->block_id;
        $visitor->total_members = $request->total_members;
        $visitor->head_name = $request->head_name;
        $visitor->head_phone = $request->head_phone;
        $visitor->stay_from = $request->stay_from;
        $visitor->stay_to = $request->stay_to;
        $visitor->visiting_purpose = $request->visiting_purpose;
        $visitor->type = $request->type;
        $visitor->stay_option = $request->stay_option;
        
                if($request->visitor_id){
            //
        }else{
            if($request->type == 'Planned'){
                $visitor->status = 'AllowIn';
            }
        }
        $visitor->save();
        
$visitor->save();

// Check if stay_to changed
$isStayToChanged = $oldStayTo != $visitor->stay_to;

// Run only when user extends stay AND visitor was already overstaying
if ($isStayToChanged && $visitor->over_stay_count > 0) {

    $title = 'Visitor Stay Time Extended';
    $body = "Visitor $visitor->head_name for Flat $flat->name has extended the stay time.";

    $categoryId = $visitor->type == 'Planned' ? 'PlannedVisitors' : 'UnplannedVisitors';

    $dataPayload = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'screen' => 'SelectedOngoingVisitor',
        'params' => json_encode([
            'visitor_id' => $visitor->id,
            'flat_id' => $flat->id,
            'user_id' => $user->id
        ]),
        'categoryId' => $categoryId,
        'channelId' => 'longring',
        'sound' => 'longring.wav',
        'type' => 'VISITOR_STAY_END',
    ];

    NotificationHelper::sendNotification(
        $visitor->security_id,
        $title,
        $body,
        $dataPayload,
        [
            'from_id' => $visitor->security_id,
            'flat_id' => $flat->id,
            'type' => 'issue_accepted',
            'apns_client' => $this->apnsClient ?? null,
            'ios_sound' => 'longring.wav'
        ]
    );

    // Reset count
    $visitor->over_stay_count = 0;
    $visitor->save();
    
    Log::info("updated extend Visitor {$visitor->id}"
    // [
    //             'flat_id' => $flat->id,
    //             'user_id' => $user->id,
    //             'stay_to' => $visitor->stay_to,
    //         ]
            );
}

        

        
        // $visitor->user_id = $user->id;
        // $visitor->building_id = $flat->building_id;
        // $visitor->flat_id = $flat->id;
        // $visitor->block_id = $flat->block_id;
        // $visitor->total_members = $request->total_members;
        // $visitor->head_name = $request->head_name;
        // $visitor->head_phone = $request->head_phone;
        // $visitor->stay_from = $request->stay_from;
        // $visitor->stay_to = $request->stay_to;
        // $visitor->visiting_purpose = $request->visiting_purpose;
        // $visitor->type = $request->type;
        // $visitor->stay_option = $request->stay_option;
        // if($request->visitor_id){
        //     //
        // }else{
        //     if($request->type == 'Planned'){
        //         $visitor->status = 'AllowIn';
        //     }
        // }
        // $visitor->save();
        
        //         if($visitor->over_stay_count>0){
                    
        //         $title = 'Visitor Stay Time Extended';
        //         $body = "Visitor $visitor->head_name for Flat $flat->name has extended the stay time.";

        //     $categoryId = $visitor->type == 'Planned' ? 'PlannedVisitors' : 'UnplannedVisitors';

        //     $dataPayload = [
        //         'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //         'screen' => 'SelectedOngoingVisitor',
        //         'params' => json_encode([
        //             //   'item' => $flat,
        //             'visitor_id' => $visitor->id,
        //             'flat_id' => $flat->id,
        //             'user_id'=>$user->id
        //         ]),
        //         'categoryId' => $categoryId,
        //         'channelId' => 'longring',
        //         'sound' => 'longring.wav',
        //         'type' => 'VISITOR_STAY_END',
        //         // 'actionButtons' => json_encode(["Extend Stay", "Mark as Left"]),
        //     ];

        //     // 🚀 Send notification to the correct user
        //     NotificationHelper::sendNotification(
        //         $visitor->security_id,
        //         $title,
        //         $body,
        //         $dataPayload,
        //          [
        //         'from_id' => $visitor->security_id,  // From the person accepting
        //         'flat_id' => $flat->id,
        //         // 'building_id' => $flat->building_id,
        //         'type' => 'issue_accepted',
        //         'apns_client' => $this->apnsClient ?? null,
        //         'ios_sound' => 'longring.wav'
        //     ]);
            
        //     $visitor->over_stay_count=0;
        //           $visitor->save();
        // }
        
        return response()->json([
                'visitor' => $visitor,
                'msg' => 'Visitor added successfully'
        ],200);
    }

    public function delete_visitor(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $visitor = Visitor::where('id',$request->visitor_id)->where('user_id',Auth::User()->id)->withTrashed()->first();
        if(!$visitor){
            return response()->json([
                'error' => 'Visitor not found'
            ],422);
        }
        $visitor_inouts = VisitorInout::where('visitor_id',$visitor->id)->get();
        foreach($visitor_inouts as $visitor_inout){
            $visitor_inout->forceDelete();
        }
        $visitor->forceDelete();
        return response()->json([
                'msg' => 'Visitor deleted successfully'
        ],200);
    }
    
    public function update_checkin_checkout_status(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $visitor = Visitor::where('id',$request->visitor_id)->withTrashed()->first();
        if(!$visitor){
            return response()->json([
                'error' => 'Visitor not found'
            ],422);
        }
        $visitor->checkin_checkout_status = $request->checkin_checkout_status;
        $visitor->save();
        return response()->json([
                'msg' => 'Visitor checkin checkout status updated successfully'
        ],200);
    }
    
    public function resend_visitor_request(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $visitor = Visitor::find($request->visitor_id);
        
        if ($visitor->request_count==2) {
            return response()->json([
                'status' => 'error',
                'error' => 'You can only resend the visitor request twice.'
            ], 422);
        }
        
        $visitor->updated_at = now();
        $visitor->request_count += 1;
        
        $visitor->save();
        

        
        $flat = $visitor->flat;
        
        if(!$flat){
            return response()->json([
                'visitor' => $visitor,
                'msg' => 'Visitor request sent successfully'
            ],200);
        }
      if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
        // $devices = DB::table('user_devices')
        //     ->where('user_id', $user->id)
        //     ->whereNotNull('fcm_token')
        //     ->where('is_active', 1)
        //     ->select('fcm_token', 'device_type')
        //     ->get();
    
        // Setup Firebase for Android/Web
        $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
        $firebaseMessaging = $firebaseFactory->createMessaging();
    
        $apnsClient = $this->apnsClient;
        if($visitor->type == 'Planned'){
            $screentab = 'Planned Visitors';
            $categoryId = 'PlannedVisitors';
        }else{
            $screentab = 'Unplanned Visitors';
            $categoryId = 'UnplannedVisitors';
        }
        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'Visitors',
            'params' => json_encode(['ScreenTab' => $screentab,'visitor_id' => $visitor->id]),
            'categoryId' => $categoryId,
            'channelId' => 'longring',
            'sound' => 'longring.wav',
            'type' => 'UNPLANNED_VISITOR_COMPLETED',
            'user_id' => (string)$user->id,
            'flat_id' => (string)$flat->id,
            'building_id' => (string)$flat->building_id,
            'actionButtons' => json_encode(["Allow", "Deny", "Stay at Lobby"]),
        ];
        // $title = 'Visitor Approval Needed';
        // $body = 'Security has raised an unplanned visitor request. Please respond with an action.';
        
        
        $title = 'Visitor Request Reminder';
        $body = 'This is a reminder to review and respond to the pending visitor request raised by security.';


        
        // foreach ($devices as $device) {
        //     $token = $device->fcm_token;
        //     $type = strtolower($device->device_type); // ios, android, web
    
        //     if (in_array($type, ['android', 'web'])) {
        //         $message = CloudMessage::withTarget('token', $token)
        //             ->withNotification(Notification::create(
        //                 $title,
        //                 $body
        //             ))
        //             ->withData($dataPayload);
    
        //         try {
        //             $firebaseMessaging->send($message);
        //             // \Log::info("Firebase notification sent to: $token");
        //         } catch (\Exception $e) {
        //             \Log::error("FCM error for token $token: " . $e->getMessage());
        //         }
    
        //     } elseif ($type === 'ios') {
        //         $alert = Alert::create()
        //             ->setTitle($title)
        //             ->setBody($body);
        //         $payload = Payload::create()
        //             ->setAlert($alert)
        //             ->setSound('bellnotificationsound.wav')
        //             ->setCustomValue('click_action', $dataPayload['click_action'])
        //             ->setCustomValue('screen', $dataPayload['screen'])
        //             ->setCustomValue('params', $dataPayload['params'])
        //             ->setCustomValue('categoryId', $dataPayload['categoryId'])
        //             ->setCustomValue('channelId', $dataPayload['channelId'])
        //             ->setCustomValue('sound', $dataPayload['sound'])
        //             ->setCustomValue('type', $dataPayload['type'])
        //             ->setCustomValue('user_id', $dataPayload['user_id'])
        //             ->setCustomValue('flat_id', $dataPayload['flat_id'])
        //             ->setCustomValue('building_id', $dataPayload['building_id'])
        //             ->setCustomValue('actionButtons', $dataPayload['actionButtons']);
                    
        //         $notification = new ApnsNotification($payload, $token);

        //         try {
        //             $apnsClient->addNotification($notification);
        //             $responses = $apnsClient->push(); // returns an array of ApnsResponseInterface
        //             foreach ($responses as $response) {
        //                 if ($response->getStatusCode() === 200) {
        //                     // Push was successful
        //                 } else {
        //                     // Push failed, optionally log the error
        //                     \Log::error('APNs Error', [
        //                         'status' => $response->getStatusCode(),
        //                         'reason' => $response->getReasonPhrase(),
        //                         'error'  => $response->getErrorReason()
        //                     ]);
        //                 }
        //             }
        //         } catch (\Exception $e) {
        //             \Log::error("APNs exception: " . $e->getMessage());
        //         }
        //     }
        // }
        
            // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $user->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ]
    );
        
        return response()->json([
                'visitor' => $visitor,
                'msg' => 'Visitor request sent successfully'
        ],200);
        
    }
    
    public function cancel_visitor_request(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $visitor = Visitor::find($request->visitor_id);
        $visitor->status = 'Cancelled';
        $visitor->save();
        
        $flat = $visitor->flat;
        
        if(!$flat){
            return response()->json([
                'msg' => 'Visitor status updated successfully'
            ], 200);
        }
          if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
        $devices = DB::table('user_devices')
            ->where('user_id', $user->id)
            ->whereNotNull('fcm_token')
            ->where('is_active', 1)
            ->select('fcm_token', 'device_type')
            ->get();
    
        // Setup Firebase for Android/Web
        $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
        $firebaseMessaging = $firebaseFactory->createMessaging();
        
        if($visitor->type == 'Planned'){
            $screentab = 'Planned Visitors';
            $categoryId = 'PlannedVisitors';
        }else{
            $screentab = 'Unplanned Visitors';
            $categoryId = 'UnplannedVisitors';
        }
        $apnsClient = $this->apnsClient;
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'Visitors',
            'params' => json_encode(['ScreenTab' => $screentab,'visitor_id' => $visitor->id]),
            'categoryId' => $categoryId,
            'channelId' => 'security',
            'sound' => 'bellnotificationsound.wav',
            'type' => 'UNPLANNED_VISITOR_COMPLETED',
            'user_id' => (string)$user->id,
            'flat_id' => (string)$flat->id,
            'building_id' => (string)$flat->building_id,
        ];
        $title = 'Visitor request Cancelled';
        $body = 'Visitor request for #['.$visitor->head_name.'] has Cancelled by security.';
        foreach ($devices as $device) {
            $token = $device->fcm_token;
            $type = strtolower($device->device_type); // ios, android, web
    
            if (in_array($type, ['android', 'web'])) {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification(Notification::create(
                        $title,
                        $body
                    ))
                    ->withData($dataPayload);
    
                try {
                    $firebaseMessaging->send($message);
                    // \Log::info("Firebase notification sent to: $token");
                } catch (\Exception $e) {
                    \Log::error("FCM error for token $token: " . $e->getMessage());
                }
    
            } elseif ($type === 'ios') {
                $alert = Alert::create()
                    ->setTitle($title)
                    ->setBody($body);
                $payload = Payload::create()
                    ->setAlert($alert)
                    ->setSound('bellnotificationsound.wav')
                    ->setCustomValue('click_action', $dataPayload['click_action'])
                    ->setCustomValue('screen', $dataPayload['screen'])
                    ->setCustomValue('params', $dataPayload['params'])
                    ->setCustomValue('categoryId', $dataPayload['categoryId'])
                    ->setCustomValue('channelId', $dataPayload['channelId'])
                    ->setCustomValue('sound', $dataPayload['sound'])
                    ->setCustomValue('type', $dataPayload['type'])
                    ->setCustomValue('user_id', $dataPayload['user_id'])
                    ->setCustomValue('flat_id', $dataPayload['flat_id'])
                    ->setCustomValue('building_id', $dataPayload['building_id']);
                $notification = new ApnsNotification($payload, $token);

                try {
                    $apnsClient->addNotification($notification);
                    $responses = $apnsClient->push(); // returns an array of ApnsResponseInterface
                    foreach ($responses as $response) {
                        if ($response->getStatusCode() === 200) {
                            // Push was successful
                        } else {
                            // Push failed, optionally log the error
                            \Log::error('APNs Error', [
                                'status' => $response->getStatusCode(),
                                'reason' => $response->getReasonPhrase(),
                                'error'  => $response->getErrorReason()
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("APNs exception: " . $e->getMessage());
                }
            }
        }
        
        return response()->json([
                'visitor' => $visitor,
                'msg' => 'Visitor request sent successfully'
        ],200);
        
    }
    
    public function create_gate_pass(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
            'desc' => 'required',
            'image' => 'nullable|image|max:2048',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        
        $visitor = Visitor::find($request->visitor_id);
        $gate_pass = new GatePass();
        // if($request->hasFile('image')) {
        //     $file= $request->file('image');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($gate_pass->getImageFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/gate_pass/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $gate_pass->image = $filename;
        // }
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($gate_pass->image_filename)) {
                $file_path = public_path('images/' . $gate_pass->image_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'gate_pass/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/gate_pass/'), $filename);
            $gate_pass->image = $filename;
        }
        $gate_pass->user_id = Auth::User()->id;
        $gate_pass->visitor_id = $visitor->id;
        $gate_pass->flat_id = $visitor->flat_id;
        $gate_pass->building_id = $visitor->building_id;
        $gate_pass->status = 'Approved';
        $gate_pass->desc = $request->desc;
        $code = mt_rand(100000, 999999);
        $gate_pass->code = $code;
        $gate_pass->save();
        
        return response()->json([
                'gate_pass' => $gate_pass,
                'msg' => 'Gate pass created successfully'
        ],200);
    }
    
    public function get_gate_passes(Request $request)
    {
        $rules = [
            'status' => 'required|in:Approved,Rejected,Recheck,Completed,All',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        if($request->status == 'All'){
            $gate_passes = GatePass::where('user_id',Auth::User()->id)->with(['user','visitor','building','flat.block'])->get();
        }else{
            $gate_passes = GatePass::where('user_id',Auth::User()->id)->where('status',$request->status)->with(['user','visitor','building','flat.block'])->get();
        }
        
        return response()->json([
                'gate_passes' => $gate_passes,
        ],200);
    }
    
    public function take_gate_pass_action(Request $request)
    {
        $rules = [
            'gate_pass_id' => 'required|exists:gate_passes,id',
            'status' => 'required|in:Approved,Rejected,RejectedExtraItem,ApprovedExtraItem,CheckedOut,CheckedOutExtraItem,storedExtraItemSecurity,ExtraItemGivenUser',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $gate_pass = GatePass::find($request->gate_pass_id);
        // if($request->hasFile('extra_image')) {
        //     $file= $request->file('extra_image');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($gate_pass->getExtraImageFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/gate-pass/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $gate_pass->extra_image = $filename;
        // }
        if ($request->hasFile('extra_image')) {
            $file = $request->file('extra_image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($gate_pass->extra_image_filename)) {
                $file_path = public_path('images/' . $gate_pass->extra_image_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'gate_pass/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/gate_pass/'), $filename);
            $gate_pass->extra_image = $filename;
        }
        $gate_pass->status = $request->status;
        $gate_pass->save();
        
        $flat = $gate_pass->flat;
        $building = $flat->building;
        // $guards = $building->guards;
        // $gate_pass->security_id
   
        if($gate_pass->status == 'RejectedExtraItem')
        {
            $title = 'Extra Item Denied by User';
            $body = 'User of ['.$flat->name.'] ['.$flat->block->name.'] has denied the extra item in gate pass #[gate_pass_id]. Please store it at security.';
            
            
            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'SelectedGatePassHandler',
                'params' => json_encode(['ScreenTab' => 'Discrepancy',
                'gate_pass_id' => $gate_pass->id,
                'visitor_id' => $gate_pass->visitor_id,
                'flat_id'=>$flat->id,
                'building_id'=>$flat->building_id,
                ]),
                
                'categoryId' => 'GatePassDiscrepancy',
                'channelId' => 'GatePass',
                'sound' => 'bellnotificationsound.wav',
                'type' => 'GATEPASS_REJECTED_BY_SECURITY',
            ];
        }
        if($gate_pass->status == 'ApprovedExtraItem')
        {
            $title = 'Extra Item Approved';
            $body = 'User of ['.$flat->name.'] ['.$flat->block->name.'] has approved the extra item. Please allow checkout with the additional item.';
            
            
            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'SelectedGatePassHandler',
                'params' => json_encode([
                'ScreenTab' => 'Discrepancy Approved',
                'gate_pass_id' => $gate_pass->id,
                'visitor_id' => $gate_pass->visitor_id,
                'flat_id'=>$flat->id,
                'building_id'=>$flat->building_id,
                ]),
                'categoryId' => 'GatePassUpdate',
                'channelId' => 'GatePass',
                'sound' => 'bellnotificationsound.wav',
                'type' => 'GATEPASS_APPROVED_EXTRA_ITEM',
            ];
        }
        
      
                    // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $gate_pass->security_id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $gate_pass->security_id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => $dataPayload['sound']
            ]
    );
       
       
        return response()->json([
            'msg' => 'Gate pass status updated',
            // '$dataPayload'=>$dataPayload
  
        ], 200);
    }
    
    public function take_gate_pass_action_building(Request $request)
    {
        $rules = [
            'gate_pass_id' => 'required|exists:gate_passes,id',
            'status' => 'required|in:Rejected,CheckedOut,CheckedOutExtraItem,storedExtraItemSecurity,ExtraItemGivenUser',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $gate_pass = GatePass::find($request->gate_pass_id);
        // if($request->hasFile('extra_image')) {
        //     $file= $request->file('extra_image');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($gate_pass->getExtraImageFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/gate-pass/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $gate_pass->extra_image = $filename;
        // }
        if ($request->hasFile('extra_image')) {
            $file = $request->file('extra_image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($gate_pass->extra_image_filename)) {
                $file_path = public_path('images/' . $gate_pass->extra_image_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'gate_pass/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/gate_pass/'), $filename);
            $gate_pass->extra_image = $filename;
        }
        $gate_pass->security_id = Auth::Id();;
        $gate_pass->status = $request->status;
        $gate_pass->save();
        
        $flat = $gate_pass->flat;
        if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
        // $devices = DB::table('user_devices')
        //     ->where('user_id', $user->id)
        //     ->whereNotNull('fcm_token')
        //     ->where('is_active', 1)
        //     ->select('fcm_token', 'device_type')
        //     ->get();
    
    
        if($gate_pass->status == 'Rejected')
        {
            $title = 'Gate Pass Discrepancy Found';
            $body = 'Security has found a mismatch in your gate pass #['.$gate_pass->id.']. Please review and respond.';
            
            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'VisitorsViewGatePass',
                'params' => json_encode([
                'flat_id' => $gate_pass->flat_id,
                    'ScreenTab' => 'Discrepancy',
                    'gate_pass_id' => $gate_pass->id,
                    'visitor_id' => $gate_pass->visitor_id,
                'building_id' =>$flat->building_id,
                ]),
                'categoryId' => 'GatePassDiscrepancy',
                'channelId' => 'longring',
                'sound' => 'longring.wav',
                'type' => 'GATEPASS_REJECTED_BY_SECURITY',

            ];
        }
        if($gate_pass->status == 'CheckedOut')
        {
            $title = 'Gate Pass Checked Out';
            $body = 'Gate pass #['.$gate_pass->id.'] has been checked out.';

            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'VisitorsViewGatePass',
                'params' => json_encode(['ScreenTab' => 'Completed', 'gate_pass_id' => $gate_pass->id,'visitor_id' => $gate_pass->visitor_id,
                 'user_id' => $user->id,
'flat_id' => $gate_pass->flat_id,
                'building_id' =>$flat->building_id,
                ]),
                'categoryId' => 'GatePassUpdate',
                'channelId' => 'GatePass',
                'sound' => 'bellnotificationsound.wav',
                'type' => 'GATEPASS_APPROVED_EXTRA_ITEM',

            ];
        }
        if($gate_pass->status == 'CheckedOutExtraItem')
        {
            $title = 'Gate Pass with Extra Item Checked Out';
            $body = 'Gate pass #['.$gate_pass->id.'] has been checked out including the extra item.';

            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'VisitorsViewGatePass',
                'params' => json_encode(['ScreenTab' => 'Discrepancy Approved', 'gate_pass_id' => $gate_pass->id,'visitor_id' => $gate_pass->visitor_id,
                 'user_id' => $user->id,
               'flat_id' => $gate_pass->flat_id,
                'building_id' =>$flat->building_id,
                ]),
                'categoryId' => 'GatePassUpdate',
                'channelId' => 'GatePass',
                'sound' => 'bellnotificationsound.wav',
                'type' => 'GATEPASS_APPROVED_EXTRA_ITEM',
                'user_id' => (string)$user->id,
                'flat_id' => (string)$flat->id,
                'flat_id' => (string)$flat->block_id,
                'building_id' => (string)$flat->building_id,
            ];
        }
        
        if($gate_pass->status == 'storedExtraItemSecurity')
        {
            $title = 'Extra Item Stored at Security';
            $body = 'Your extra item from gate pass #['.$gate_pass->id.'] has been stored at the security desk. Please collect it at your convenience.';
            
            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'VisitorsViewGatePass',
                'params' => json_encode(['ScreenTab' => 'Pending Collection', 'gate_pass_id' => $gate_pass->id,'visitor_id' => $gate_pass->visitor_id,
                    'user_id' => $user->id,
        'flat_id' => $gate_pass->flat_id,
                'building_id' =>$flat->building_id,
                ]),
                'categoryId' => 'GatePassUpdate',
                'channelId' => 'GatePass',
                'sound' => 'bellnotificationsound.wav',
                'type' => 'GATEPASS_APPROVED_EXTRA_ITEM',
                'user_id' => (string)$user->id,
                'flat_id' => (string)$flat->id,
                'flat_id' => (string)$flat->block_id,
                'building_id' => (string)$flat->building_id,
            ];
        }
        
        if($gate_pass->status == 'ExtraItemGivenUser')
        {
            $title = 'Extra Item Collected';
            $body = 'You have successfully collected the extra item from gate pass #['.$gate_pass->id.'] at the security desk.';

            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'VisitorsViewGatePass',
                'params' => json_encode([
                    'ScreenTab' => 'Completed',
                    'gate_pass_id' => $gate_pass->id,
                    'visitor_id' => $gate_pass->visitor_id,
                 'user_id' => $user->id,
              'flat_id' => $gate_pass->flat_id,
                'building_id' =>$flat->building_id,
                    
                    ]),
                'categoryId' => 'GatePassUpdate',
                'channelId' => 'GatePass',
                'sound' => 'bellnotificationsound.wav',
                'type' => 'GATEPASS_APPROVED_EXTRA_ITEM',
            
            ];
        }
        
        
            // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $user->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => $dataPayload['sound']
            ]
    );
       
        
        return response()->json([
            'msg' => 'Gate pass status updated',
            '$notificationResult'=>$notificationResult,
            '$dataPayload'=>$dataPayload,
 
        ], 200);
    }
    
    public function gate_pass_details(Request $request)
    {
        $rules = [
            'gate_pass_id' => 'required|exists:gate_passes,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $gate_pass = GatePass::where('id',$request->gate_pass_id)->with(['user','visitor','building','flat.block'])->first();
        return response()->json([
            'gate_pass' => $gate_pass,
        ],200);
    }
    
    public function my_visitor_in_out_history(Request $request)
    {
        if($request->visitor_id){
            $visitor_inouts = Auth::User()->flat->visitor_inouts()->where('visitor_id',$request->visitor_id)->with('visitor')->get();
        }else{
            $visitor_inouts = Auth::User()->flat->visitor_inouts()->with('visitor')->get();
        }
        
        
        
        return response()->json([
            'visitor_inouts' => $visitor_inouts
        ], 200);
    }
    
    
    
    
    public function create_family_member(Request $request)
    {
        $rules = [
            'name' => 'required',
            'phone' => 'required',
            'photo' => 'nullable|image|max:5120',
            'relationship' => 'required',
            'family_member_id' => 'nullable|exists:family_members,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        
        $user = Auth::User();
        $flat = AuthHelper::flat();
        
        
        $family_member = new FamilyMember();
        $msg = 'Family member added successfully';
        if($request->family_member_id){
            $family_member = FamilyMember::find($request->family_member_id);
            $msg = 'Family member updated successfully';
        }
        
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($family_member->photo_filename)) {
                $file_path = public_path('images/' . $family_member->photo_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'family_members/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/family_members/'), $filename);
            $family_member->photo = $filename;
        }
        
        
        
        $family_member->user_id = $user->id;
        $family_member->building_id = $flat->building_id;
        $family_member->flat_id = $flat->id;
        $family_member->name = $request->name;
        $family_member->phone = $request->phone;
        $family_member->relationship = $request->relationship;
        $family_member->save();
        
        return response()->json([
                // 'family_member' => $family_member,
                'msg' => $msg,
                // '$flat'=>$flat
        ],200);
    }
    
    public function delete_family_member(Request $request)
    {
        $rules = [
            'family_member_id' => 'nullable|exists:family_members,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $user = Auth::User();
        $flat = AuthHelper::flat();
        $family_member = FamilyMember::where('flat_id',$flat->id)->where('id',$request->family_member_id)->first();
        $family_member->delete();
        return response()->json([
                'msg' => 'Family member deleted successfully'
        ],200);
    }

    public function my_family_members(Request $request)
    {
    $user = Auth::user();

    $flat_id = AuthHelper::flat()->id;

    if (!$flat_id) {
        return response()->json([
            'message' => 'User does not belong to any flat.'
        ], 404);
    }

    $family_members = FamilyMember::where('user_id', $user->id)
        ->where('flat_id', $flat_id)
        ->get();

    return response()->json([
        // '$flat_id'=>$flat_id
        'family_members' => $family_members
    ], 200);
}

    
    // public function maintenance_paymentsxx(Request $request)
    // {
    //     $user = Auth::user();
    //     $flat =AuthHelper::flat();
    //     $last_payment = MaintenancePayment::where('flat_id', $flat->id)
    //         ->where('status', 'Paid')
    //         ->orderBy('id', 'desc')
    //         ->first();
    //     $last_paid_date = $last_payment ? $last_payment->created_at->format('Y-m-d') : null;

    //     $maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
    //         ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
    //         ->where('status', 'Unpaid')
    //         ->orderBy('id', 'desc')
    //         ->get();

    //     $total_payment = 0;
    //     $total_gst = 0;
    //     foreach ($maintenance_payments as $payment) {
    //         $maintenance = $payment->maintenance;
    //         $late_fine = 0;
            
    //         $dueDate = Carbon::parse($maintenance->due_date);
    //         if ($maintenance && $dueDate->lt(now()->startOfDay())) {
    //             $late_days = $dueDate->diffInDays(now());
    //             switch ($maintenance->late_fine_type) {
    //                 case 'Daily':
    //                     $late_fine = $late_days * $maintenance->late_fine_value;
    //                     break;
    //                 case 'Fixed':
    //                     $late_fine = $maintenance->late_fine_value;
    //                     break;
    //                 case 'Percentage':
    //                     $late_fine = ($payment->dues_amount * $maintenance->late_fine_value) / 100;
    //                     break;
    //             }
    //         }

    //         $payment->late_fine = $late_fine;
    //         // $payment->total_amount = $maintenance->amount + $late_fine;
    //         $total = $payment->dues_amount + $late_fine;
    //         $payment->save();
    //         $payment->gst = $total * $payment->maintenance->gst / 100;
    //         $total_payment += $total;
    //         $total_gst += $payment->gst;
    //     }

    //     $gst = $total_gst;
    //     $grand_total = $total_payment + $gst;
    //     $grand_total = ceil($grand_total);
    //     // $paid_maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
    //     //     ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building','transaction'])
    //     //     ->where('status', 'Paid')
    //     //     ->orderBy('id', 'desc')
    //     //     ->get();
    //     // $transactions = Transaction::whereIn('user_id',[$flat->owner_id,$flat->tanent_id])->where('model','Maintenance')->with(['user.flat','maintenance_payments.maintenance'])->orderBy('id','desc')->get();
    //     // dd($flat);
    //     $transactions = Transaction::whereIn('user_id',[$flat->owner_id,$flat->tanent_id])
    //         ->where('building_id', $flat->building_id)
    //         ->where('flat_id', $flat->id)           // ← Added flat_id filter
    //         ->where('block_id', $flat->block_id)    // ← Added block_id filter
    //         ->where('model','Maintenance')
    //         ->with(['user.flat','maintenance_payments.maintenance'])
    //         ->orderBy('id','desc')
    //         ->get();
    // //     $transactions = Transaction::whereIn('user_id', [$flat->owner_id, $flat->tanent_id])
    // // ->where('model', 'Maintenance')
    // // ->with([
    // //     'user.flat',
    // //     'maintenance_payments.maintenance',
    // //     'maintenance_payments.flat' // ðŸ‘ˆ this loads the flat for each payment
    // // ])
    // // ->orderBy('id', 'desc')
    // // ->get();

    //     return response()->json([
    //         'maintenance_payments' => $maintenance_payments,
    //         // 'paid_maintenance_payments' => $paid_maintenance_payments,
    //         'transactions' => $transactions,
    //         'total_payment' => $total_payment,
    //         'gst' => $gst,
    //         'grand_total' => $grand_total,
    //         'last_paid_date' => $last_paid_date
    //     ], 200);
    // }


    public function maintenance_payments(Request $request)
    {
        $user = Auth::user();
        $flat = $flat =AuthHelper::flat();
        $last_payment = MaintenancePayment::where('flat_id', $flat->id)
            ->where('status', 'Paid')
            
            ->orderBy('id', 'desc')
            ->first();
        $last_paid_date = $last_payment ? $last_payment->created_at->format('Y-m-d') : null;

        $maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
            ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
            ->where('status', 'Unpaid')
            ->orderBy('id', 'desc')
            ->get();

        $total_payment = 0;
        $total_gst = 0;
        foreach ($maintenance_payments as $payment) {
            $maintenance = $payment->maintenance;
            $late_fine = 0;
            
            $dueDate = Carbon::parse($maintenance->due_date);
            if ($maintenance && $dueDate->lt(now()->startOfDay())) {
                $late_days = $dueDate->diffInDays(now());
                switch ($maintenance->late_fine_type) {
                    case 'Daily':
                        $late_fine = $late_days * $maintenance->late_fine_value;
                        break;
                    case 'Fixed':
                        $late_fine = $maintenance->late_fine_value;
                        break;
                    case 'Percentage':
                        $late_fine = ($payment->dues_amount * $maintenance->late_fine_value) / 100;
                        break;
                }
            }

            $payment->late_fine = $late_fine;
            // $payment->total_amount = $maintenance->amount + $late_fine;
            $total = $payment->dues_amount + $late_fine;
            $payment->save();
            $payment->gst = $total * $payment->maintenance->gst / 100;
            $total_payment += $total;
            $total_gst += $payment->gst;
        }

        $gst = $total_gst;
        $grand_total = $total_payment + $gst;
        $grand_total = ceil($grand_total);
        // $paid_maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
        //     ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building','transaction'])
        //     ->where('status', 'Paid')
        //     ->orderBy('id', 'desc')
        //     ->get();
        // $transactions = Transaction::whereIn('user_id',[$flat->owner_id,$flat->tanent_id])->where('model','Maintenance')->with(['user.flat','maintenance_payments.maintenance'])->orderBy('id','desc')->get();
        // dd($flat);
        $transactions = Transaction::whereIn('user_id',[$flat->owner_id,$flat->tanent_id])
            ->where('building_id', $flat->building_id)
            ->where('flat_id', $flat->id)           // ← Added flat_id filter
            ->where('block_id', $flat->block_id)    // ← Added block_id filter
            ->where('model','Maintenance')
            ->with(['user.flat','maintenance_payments.maintenance','maintenance_payments.flat'])
            ->orderBy('id','desc')
            ->get();
    //     $transactions = Transaction::whereIn('user_id', [$flat->owner_id, $flat->tanent_id])
    // ->where('model', 'Maintenance')
    // ->with([
    //     'user.flat',
    //     'maintenance_payments.maintenance',
    //     'maintenance_payments.flat' // ðŸ‘ˆ this loads the flat for each payment
    // ])
    // ->orderBy('id', 'desc')
    // ->get();

        return response()->json([
            'maintenance_payments' => $maintenance_payments,
            // 'paid_maintenance_payments' => $paid_maintenance_payments,
            'transactions' => $transactions,
            'total_payment' => $total_payment,
            'gst' => $gst,
            'grand_total' => $grand_total,
            'last_paid_date' => $last_paid_date
        ], 200);
    }
    
    public function create_maintenance_payment_order(Request $request)
    {
        $rules = [
            'maintenance_payment_id' => 'required|exists:maintenance_payments,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
 $flat =AuthHelper::flat();
        $maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
            // ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
            ->where('status', 'Unpaid')
            ->orderBy('id', 'desc')
            ->get();

        $total_payment = 0;
        $total_gst = 0;
        foreach ($maintenance_payments as $payment) {
            $maintenance = $payment->maintenance;
            $late_fine = 0;

            $dueDate = Carbon::parse($maintenance->due_date);
            if ($maintenance && $dueDate->lt(now()->startOfDay())) {
                $late_days = $dueDate->diffInDays(now());

                switch ($maintenance->late_fine_type) {
                    case 'Daily':
                        $late_fine = $late_days * $maintenance->late_fine_value;
                        break;
                    case 'Fixed':
                        $late_fine = $maintenance->late_fine_value;
                        break;
                    case 'Percentage':
                        $late_fine = ($payment->dues_amount * $maintenance->late_fine_value) / 100;
                        break;
                }
            }

            $payment->late_fine = $late_fine;
            // $payment->total_amount = $maintenance->amount + $late_fine;
            $total = $payment->dues_amount + $late_fine;
            $payment->save();
            $payment->gst = $total * $payment->maintenance->gst / 100;
            $total_payment += $total;
            $total_gst += $payment->gst;
        }

        $gst = $total_gst;
        $grand_total = $total_payment + $gst;

        $maintenance_payment = MaintenancePayment::find($request->maintenance_payment_id);
        $item_name = 'Maintenance Payment';
        $item_number = $maintenance_payment->id;
        $item_amount = ceil($grand_total);
        
        $orderData = [
            'receipt'         => 'RCP'.rand(10000000,99999999),
            'amount'          => $item_amount * 100, // 2000 rupees in paise
            'currency'        => 'INR',
            'payment_capture' => 1 // auto capture
        ];
        
 $flat =AuthHelper::flat();
        $building = $flat->building;
        
        if($building->maintenance_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        try{
            $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        
            $razorpayOrder = $this->api->order->create($orderData);
        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            $razorpayMessage = $e->getMessage();

            if (strpos($razorpayMessage, 'Amount exceeds maximum amount allowed') !== false) {
                $customMessage = "The amount is too large. Payment not allowed for this amount.";
            } else {
                $customMessage = "We are unable to create a payment. Please contact your building admin.";
            }
        
            return response()->json([
                'error' => $customMessage
            ], 422);
        }
        $razorpayOrderId = $razorpayOrder['id'];
        $displayAmount = $amount = $orderData['amount'];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $url = "https://api.fixer.io/latest?symbols=$this->displayCurrency&base=INR";
            $exchange = json_decode(file_get_contents($url), true);
                    
            $displayAmount = $exchange['rates'][$this->displayCurrency] * $amount / 100;
        }
                    
        $data = [
            "key"               => $building->razorpay_key,
            "amount"            => $amount,
            "name"              => $item_name,
            "description"       => $item_name,
            "prefill"           => [
    			"name"              => $user->name,
    			"email"             => $user->email,
    			"contact"           => $user->phone,
            ],
            "notes"             => [
				"address"           => $user->address,
				"merchant_order_id" => $item_number,
            ],
            "theme"             => [
				"color"             => "#3399cc"
            ],
            "order_id"          => $razorpayOrderId,
        ];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $data['display_currency']  = $this->displayCurrency;
            $data['display_amount']    = $displayAmount;
        }
                    
        $displayCurrency = $this->displayCurrency;
        
        $order = new Order();
        $order->user_id = $user->id;
        $order->order_id = $razorpayOrderId;
        $order->model = 'MaintenancePayment';
        $order->model_id = $maintenance_payment->id;
        $order->building_id = $maintenance_payment->building_id;
        $order->flat_id = $maintenance_payment->flat_id;
        $order->desc = 'Creating order for maintenance payment from '.$maintenance_payment->from_date. ' to '.$maintenance_payment->to_date;
        $order->amount = $item_amount;
        $order->status = 'Created';
        $order->save();
        
        return response()->json([
                'data' => $data,
                'displayCurrency' => $displayCurrency
        ],200);
    }
    
    public function verify_maintenance_payment_signature(Request $request)
    {
        
        $rules = [
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $order = Order::where('order_id',$request->razorpay_order_id)->where('status','Created')->first();
        $order = Order::where('order_id',$request->razorpay_order_id)->first();
        if(!$order){
            return response()->json([
                    'error' => 'Order id not found',
            ],422);
        }
        $success = true;
        $error = "Payment Failed";
        
        $razorpay_order_id = $request->razorpay_order_id;
        $razorpay_payment_id = $request->razorpay_payment_id;
        $razorpay_signature = $request->razorpay_signature;
        
     $flat =AuthHelper::flat();
        $building = $flat->building;
        
        if($building->maintenance_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        
        try{
            $attributes = array(
                'razorpay_order_id' => $razorpay_order_id,
                'razorpay_payment_id' => $razorpay_payment_id,
                'razorpay_signature' => $razorpay_signature
            );
        
            $this->api->utility->verifyPaymentSignature($attributes);
            
        }
        catch(SignatureVerificationError $e){
            $success = false;
            $error = 'Razorpay Error : ' . $e->getMessage();
            return response()->json([
                    'error' => $error
            ],400);
        }

        if ($success === true)
        {
            $razorpayOrder = $this->api->order->fetch($razorpay_order_id);
            $reciept = $razorpayOrder['receipt'];
            $transaction_id = $razorpay_payment_id;
            
            $order->payment_id = $razorpay_payment_id;
            $order->signature = $razorpay_signature;
            $order->status = 'Verified';
            $order->save();
             
            $transaction = new Transaction();
            $transaction->building_id = AuthHelper::flat()->building_id;
          
            $transaction->user_id = $user->id;
            $transaction->flat_id = $flat->id;
            $transaction->block_id = $flat->block_id;
            $transaction->order_id = $order->order_id;
            $transaction->model = 'Maintenance';
            $paid_maintenance = MaintenancePayment::find($order->model_id);
            $transaction->model_id = $paid_maintenance->maintenance_id;
            $transaction->type = 'Credit';
            $transaction->payment_type = 'InBank';
            $transaction->amount = $order->amount;
            $transaction->reciept_no = $reciept;
            $transaction->desc = 'Maintenance Payment paid by flat number '. AuthHelper::flat()->name;
            $transaction->status = 'Success';
            $transaction->date = now()->toDateString();
            $transaction->save();
            
            $maintenance_payment = $paid_maintenance;
            $maintenance_payment->user_id = $user->id;
            $maintenance_payment->paid_amount = $maintenance_payment->dues_amount;
            $maintenance_payment->paid_date = now()->toDateString();
            $maintenance_payment->dues_amount = 0;
            $maintenance_payment->type = 'Credit';
            $maintenance_payment->payment_type = 'InBank';
            $maintenance_payment->desc = 'Maintenance Payment paid by flat number '. AuthHelper::flat()->name;
            $maintenance_payment->transaction_id = $transaction->id;
            $maintenance_payment->status = 'Paid';
            $maintenance_payment->save();

            $maintenance_payments = MaintenancePayment::where('flat_id', AuthHelper::flat()->id)
            // ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
            ->where('status', 'Unpaid')
            ->orderBy('id', 'desc')
            ->get();
            
            foreach($maintenance_payments as $maintenance_payment){
                $maintenance_payment->paid_amount = $maintenance_payment->dues_amount;
                $maintenance_payment->paid_date = now()->toDateString();
                $maintenance_payment->dues_amount = 0;
                $maintenance_payment->type = 'Credit';
                $maintenance_payment->payment_type = 'InBank';
                $maintenance_payment->desc = 'Paid Through Razorpay';
                $maintenance_payment->transaction_id = $transaction->id;
                $maintenance_payment->status = 'Paid';
                $maintenance_payment->save();
            }
            
            return response()->json([
                    'message' => 'Payment completed! You can now download or view your reciept'
            ],200);

        }
        else
        {
            return response()->json([
                    'error' => $error
            ],422);
        }
    }
    
    public function maintenance_invoice(Request $request)
    {
        $rules = [
            'maintenance_payment_id' => 'required|exists:maintenance_payments,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            
            return response()->json([
                'error' => $validation->errors()->first()
            ],422);
        }
        $maintenance_payment_id = $request->maintenance_payment_id;
        $maintenance_payment = MaintenancePayment::where('id',$maintenance_payment_id)->where('flat_id',AuthHelper::flat()->flat_id)->first();
        if(!$maintenance_payment){
            return response()->json([
                'error' => 'Maintenance payment not found'
            ],422);
        }
        $flat = $maintenance_payment->flat;
        if($flat->tanent){
            $user = $flat->tanent;
        }else{
            $user = $flat->owner;
        }
        
        $last_payment = MaintenancePayment::where('flat_id', $flat->id)
            ->where('status', 'Paid')
            ->where('id', '<', $maintenance_payment_id)
            ->orderBy('id', 'desc')
            ->first();
        $last_paid_date = $last_payment ? $last_payment->created_at->format('Y-m-d') : 'N/A';
        
        $transaction = $maintenance_payment->transaction;
        if(!$transaction){
            $maintenance_payments = MaintenancePayment::where('flat_id',$maintenance_payment->flat_id)
                ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
                ->where('status', 'Unpaid')
                ->orderBy('id', 'desc')
                ->get();
        }else{
            $maintenance_payments = $transaction->maintenance_payments()
                ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
                ->orderBy('id', 'desc')
                ->get();
        }

        $total_payment = 0;
        $total_gst = 0;
        foreach ($maintenance_payments as $payment) {
            $maintenance = $payment->maintenance;
            $late_fine = 0;

            $dueDate = Carbon::parse($maintenance->due_date);
            if ($maintenance && $dueDate->lt(now()->startOfDay())) {
                $late_days = $dueDate->diffInDays(now());

                switch ($maintenance->late_fine_type) {
                    case 'Daily':
                        $late_fine = $late_days * $maintenance->late_fine_value;
                        break;
                    case 'Fixed':
                        $late_fine = $maintenance->late_fine_value;
                        break;
                    case 'Percentage':
                        $late_fine = ($payment->paid_amount * $maintenance->late_fine_value) / 100;
                        break;
                }
            }

            $payment->late_fine = $late_fine;
            // $payment->total_amount = $maintenance->amount + $late_fine;
            if($payment->status == 'Paid'){
                $total = $payment->paid_amount + $late_fine;
            }else{
                $total = $payment->dues_amount + $late_fine;
            }
            $payment->save();
            $payment->gst = $total * $payment->maintenance->gst / 100;
            $total_payment += $total;
            $total_gst += $payment->gst;
        }

        $gst = $total_gst;
        $grand_total = $total_payment + $gst;
        // $paid_maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
        //     ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building','transaction'])
        //     ->where('status', 'Paid')
        //     ->orderBy('id', 'desc')
        //     ->get();
        $transactions = Transaction::where('user_id',$user->id)->where('model','Maintenance')->with(['user','maintenance_payments.maintenance'])->orderBy('id','desc')->get();
        
        $pdf = Pdf::loadView('partials.invoice.maintenance', [
            'flat' => $flat,
            'maintenance_payments' => $maintenance_payments,
            'transactions' => $transactions,
            'total_payment' => $total_payment,
            'gst' => $gst,
            'grand_total' => $grand_total,
            'last_paid_date' => $last_paid_date,
            'user' => $user,
        ]);

        return $pdf->download('invoice.pdf');
    
        // return view('partials.invoice.maintenance',compact('flat','maintenance_payments','transactions','total_payment','gst','grand_total','last_paid_date','user'));
    }
    
    public function maintenance_reciept(Request $request)
    {
        $maintenance_payment_id = $request->maintenance_payment_id;
        $maintenance_payment = MaintenancePayment::where('id',$maintenance_payment_id)->where('flat_id',AuthHelper::flat()->flat_id)->first();

        if(!$maintenance_payment){
            return response()->json([
                'error' => 'Maintenance payment not found'
            ],422);
        }
        $flat = $maintenance_payment->flat;
        if($flat->tanent){
            $user = $flat->tanent;
        }else{
            $user = $flat->owner;
        }
        
        $last_payment = MaintenancePayment::where('flat_id', $flat->id)
            ->where('status', 'Paid')
            ->where('id', '<', $maintenance_payment_id)
            ->orderBy('id', 'desc')
            ->first();
        $last_paid_date = $last_payment ? $last_payment->created_at->format('Y-m-d') : 'N/A';
        
        $transaction = $maintenance_payment->transaction;
        if(!$transaction){
            return response()->json([
                'error' => 'Transaction not found'
            ],422);
        }
        $maintenance_payments = $transaction->maintenance_payments()
            ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
            ->orderBy('id', 'desc')
            ->get();

        $total_payment = 0;
        $total_gst = 0;
        foreach ($maintenance_payments as $payment) {
            $maintenance = $payment->maintenance;
            $late_fine = 0;

            $dueDate = Carbon::parse($maintenance->due_date);
            if ($maintenance && $dueDate->lt(now()->startOfDay())) {
                $late_days = $dueDate->diffInDays(now());

                switch ($maintenance->late_fine_type) {
                    case 'Daily':
                        $late_fine = $late_days * $maintenance->late_fine_value;
                        break;
                    case 'Fixed':
                        $late_fine = $maintenance->late_fine_value;
                        break;
                    case 'Percentage':
                        $late_fine = ($payment->paid_amount * $maintenance->late_fine_value) / 100;
                        break;
                }
            }

            $payment->late_fine = $late_fine;
            // $payment->total_amount = $maintenance->amount + $late_fine;
            $total = $payment->paid_amount + $late_fine;
            $payment->save();
            $payment->gst = $total * $payment->maintenance->gst / 100;
            $total_payment += $total;
            $total_gst += $payment->gst;
        }

        $gst = $total_gst;
        $grand_total = $total_payment + $gst;
        // $paid_maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
        //     ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building','transaction'])
        //     ->where('status', 'Paid')
        //     ->orderBy('id', 'desc')
        //     ->get();
        $transactions = Transaction::where('user_id',$user->id)->where('model','Maintenance')->with(['user','maintenance_payments.maintenance'])->orderBy('id','desc')->get();
        
        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])
        ->loadView('partials.reciept.maintenance', [
            'flat' => $flat,
            'maintenance_payments' => $maintenance_payments,
            'transactions' => $transactions,
            'total_payment' => $total_payment,
            'gst' => $gst,
            'grand_total' => $grand_total,
            'last_paid_date' => $last_paid_date,
            'user' => $user,
        ]);

        return $pdf->download('reciept.pdf');
        // return view('admin.account.maintenance.reciept_maintenance',compact('flat','maintenance_payments','transactions','total_payment','gst','grand_total','last_paid_date','user'));
    }
    
    
    
    public function essential_payments(Request $request)
    {
        $user = Auth::User();
        $essential_payments = EssentialPayment::where('user_id',$user->id)->with(['essential','flat.owner','flat.tanent','flat.block','flat.building','reciept'])->orderBy('id','desc')->get();
        return response()->json([
                'essential_payments' => $essential_payments,

        ],200);
    }
    
    public function create_essential_payment_order(Request $request)
    {
        $rules = [
            'essential_payment_id' => 'required|exists:essential_payments,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $flat = AuthHelper::flat();
        $essential_payment = EssentialPayment::find($request->essential_payment_id);
        $item_name = 'Essential Payment';
        $item_number = $essential_payment->id;
        $dues_amount = $essential_payment->dues_amount;
        $late_fine = 0;
        $gst = $essential_payment->essential->gst;
        
        
        if ($essential_payment->essential->status=="Inactive") {
            return response()->json([
                'error' => "This essential payment is inactive and cannot be processed. Please Refresh the page"
            ], 422);
        }
        
        $dueDate = Carbon::parse($essential_payment->essential->due_date);
        if ($essential_payment && $dueDate->lt(now()->startOfDay())) {
            $late_days = $dueDate->diffInDays(now());
            switch ($essential_payment->essential->late_fine_type) {
                case 'Daily':
                    $late_fine = $late_days * $essential_payment->essential->late_fine_value;
                    break;
                case 'Fixed':
                    $late_fine = $essential_payment->essential->late_fine_value;
                    break;
                case 'Percentage':
                    $late_fine = ($essential_payment->dues_amount * $essential_payment->essential->late_fine_value) / 100;
                    break;
                }
        }
        $total_amount = $dues_amount + $late_fine;
        $total_gst = $total_amount * $gst / 100;
        $item_amount = $total_amount + $total_gst;
        $item_amount = ceil($item_amount);
        $orderData = [
            'receipt'         => 'RCP'.rand(10000000,99999999),
            'amount'          => $item_amount * 100, // 2000 rupees in paise
            'currency'        => 'INR',
            'payment_capture' => 1 // auto capture
        ];
        $flat = AuthHelper::flat();
        $building = $flat->building;
        
        if($building->other_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        try{
            $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        
            $razorpayOrder = $this->api->order->create($orderData);
        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            $razorpayMessage = $e->getMessage();

            if (strpos($razorpayMessage, 'Amount exceeds maximum amount allowed') !== false) {
                $customMessage = "The amount is too large. Payment not allowed for this amount.";
            } else {
                $customMessage = "We are unable to create a payment. Please contact your building admin.";
            }
        
            return response()->json([
                'error' => $customMessage
            ], 422);
        }
        $razorpayOrderId = $razorpayOrder['id'];
        $displayAmount = $amount = $orderData['amount'];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $url = "https://api.fixer.io/latest?symbols=$this->displayCurrency&base=INR";
            $exchange = json_decode(file_get_contents($url), true);
                    
            $displayAmount = $exchange['rates'][$this->displayCurrency] * $amount / 100;
        }
                    
        $data = [
            "key"               => $building->razorpay_key,
            "amount"            => $amount,
            "name"              => $item_name,
            "description"       => $item_name,
            "prefill"           => [
    			"name"              => $user->name,
    			"email"             => $user->email,
    			"contact"           => $user->phone,
            ],
            "notes"             => [
				"address"           => $user->address,
				"merchant_order_id" => $item_number,
            ],
            "theme"             => [
				"color"             => "#3399cc"
            ],
            "order_id"          => $razorpayOrderId,
        ];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $data['display_currency']  = $this->displayCurrency;
            $data['display_amount']    = $displayAmount;
        }
                    
        $displayCurrency = $this->displayCurrency;
        
        $order = new Order();
        $order->user_id = $user->id;
        $order->order_id = $razorpayOrderId;
        $order->model = 'EssentialPayment';
        $order->model_id = $essential_payment->id;
        $order->building_id = $essential_payment->building_id;
        $order->flat_id = $essential_payment->flat_id;
        $order->desc = 'Creating order for essential payment for '.$essential_payment->flat->name;
        $order->amount = $item_amount;
        $order->status = 'Created';
        $order->save();
        
        return response()->json([
                'data' => $data,
                'displayCurrency' => $displayCurrency
        ],200);
    }
    
    public function verify_essential_payment_signature(Request $request)
    {
        $rules = [
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $order = Order::where('order_id',$request->razorpay_order_id)->where('status','Created')->first();
        // $order = Order::where('order_id',$request->razorpay_order_id)->first();
        if(!$order){
            return response()->json([
                    'error' => 'Order id not found',
            ],422);
        }
        $success = true;
        $error = "Payment Failed";
        
        $razorpay_order_id = $request->razorpay_order_id;
        $razorpay_payment_id = $request->razorpay_payment_id;
        $razorpay_signature = $request->razorpay_signature;
        
     $flat = AuthHelper::flat();
     
        $building = $flat->building;
        if($building->other_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        try{
            $attributes = array(
                'razorpay_order_id' => $razorpay_order_id,
                'razorpay_payment_id' => $razorpay_payment_id,
                'razorpay_signature' => $razorpay_signature
            );
        
            $this->api->utility->verifyPaymentSignature($attributes);
            
        }
        catch(SignatureVerificationError $e){
            $success = false;
            $error = 'Razorpay Error : ' . $e->getMessage();
            return response()->json([
                    'error' => $error
            ],400);
        }

        if ($success === true)
        {
            $razorpayOrder = $this->api->order->fetch($razorpay_order_id);
            $reciept = $razorpayOrder['receipt'];
            $transaction_id = $razorpay_payment_id;
            
            $order->payment_id = $razorpay_payment_id;
            $order->signature = $razorpay_signature;
            $order->status = 'Verified';
            $order->save();
            
            $transaction = new Transaction();
            $transaction->building_id = $flat->building_id;
            $transaction->user_id = $user->id;
            $transaction->flat_id = $flat->id;
            $transaction->block_id = $flat->block_id;
            $transaction->order_id = $order->order_id;
            $transaction->model = 'Essential';
            $paid_essential = EssentialPayment::find($order->model_id);
            $transaction->model_id = $paid_essential->essential->id;
            $transaction->type = 'Credit';
            $transaction->payment_type = 'InBank';
            $transaction->amount = $order->amount;
            $transaction->reciept_no = $reciept;
            $transaction->desc = 'Essential Payment paid by flat number '.$flat->name;
            $transaction->status = 'Success';
            $transaction->date = now()->toDateString();
            $transaction->save();
            
            $essential_payment = $paid_essential;
            $essential_payment->paid_amount = $order->amount;
            $essential_payment->dues_amount = 0;
            $essential_payment->type = 'Credit';
            $essential_payment->payment_type = 'InBank';
            $essential_payment->status = 'Paid';
            $essential_payment->date = now()->toDateTimeString();

            $essential_payment->transaction_id = $transaction->id;

            $essential_payment->save();
            
            
            return response()->json([
                    'message' => 'Payment completed! You can now download or view your reciept',
                    'flat'=>$flat
            ],200);

        }
        else
        {
            return response()->json([
                    'error' => $error
            ],422);
        }
    }
    
    public function create_event_payment_order(Request $request)
    {
        $rules = [
            'event_id' => 'required|exists:events,id',
            'amount' => 'required|integer'
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $flat = AuthHelper::flat();
        
        $event = Event::find($request->event_id);
        $item_name = 'Event Payment';
        $item_number = $event->id;
        $item_amount = $request->amount;
        $item_amount = ceil($item_amount);
        $orderData = [
            'receipt'         => 'RCP'.rand(10000000,99999999),
            'amount'          => $item_amount * 100, // 2000 rupees in paise
            'currency'        => 'INR',
            'payment_capture' => 1 // auto capture
        ];
        
        $flat = AuthHelper::flat();
       
        $building = $flat->building;
        if($building->donation_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        
        try{
            $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        
            $razorpayOrder = $this->api->order->create($orderData);
        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            $razorpayMessage = $e->getMessage();

            if (strpos($razorpayMessage, 'Amount exceeds maximum amount allowed') !== false) {
                $customMessage = "The amount is too large. Payment not allowed for this amount.";
            } else {
                $customMessage = "We are unable to create a payment. Please contact your building admin.";
            }
        
            return response()->json([
                'error' => $customMessage
            ], 422);
        }
        $razorpayOrderId = $razorpayOrder['id'];
        $displayAmount = $amount = $orderData['amount'];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $url = "https://api.fixer.io/latest?symbols=$this->displayCurrency&base=INR";
            $exchange = json_decode(file_get_contents($url), true);
                    
            $displayAmount = $exchange['rates'][$this->displayCurrency] * $amount / 100;
        }
                    
        $data = [
            "key"               => $building->razorpay_key,
            "amount"            => $amount,
            "name"              => $item_name,
            "description"       => $item_name,
            "prefill"           => [
    			"name"              => $user->name,
    			"email"             => $user->email,
    			"contact"           => $user->phone,
            ],
            "notes"             => [
				"address"           => $user->address,
				"merchant_order_id" => $item_number,
            ],
            "theme"             => [
				"color"             => "#3399cc"
            ],
            "order_id"          => $razorpayOrderId,
        ];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $data['display_currency']  = $this->displayCurrency;
            $data['display_amount']    = $displayAmount;
        }
                    
        $displayCurrency = $this->displayCurrency;
        $event = Event::find($request->event_id);
        
        $order = new Order();
        $order->user_id = $user->id;
        $order->order_id = $razorpayOrderId;
        $order->model = 'Event';
        $order->model_id = $event->id;
        $order->building_id = $event->building_id;
        $order->flat_id = $flat->id;
        $order->desc = 'Creating order for event '.$event->name;
        $order->amount = $item_amount;
        $order->status = 'Created';
        $order->save();
        
        return response()->json([
                'data' => $data,
                'displayCurrency' => $displayCurrency
        ],200);
    }
    
    public function verify_event_payment_signature(Request $request)
    {
        $rules = [
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        // $order = Order::where('order_id',$request->razorpay_order_id)->where('status','Created')->first();
        $order = Order::where('order_id',$request->razorpay_order_id)->first();
        if(!$order){
            return response()->json([
                    'error' => 'Order id not found',
            ],422);
        }
        $success = true;
        $error = "Payment Failed";
        
        $razorpay_order_id = $request->razorpay_order_id;
        $razorpay_payment_id = $request->razorpay_payment_id;
        $razorpay_signature = $request->razorpay_signature;
        
        $flat =  AuthHelper::flat();
        $building = $flat->building;
        if($building->donantion_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        try{
            $attributes = array(
                'razorpay_order_id' => $razorpay_order_id,
                'razorpay_payment_id' => $razorpay_payment_id,
                'razorpay_signature' => $razorpay_signature
            );
        
            $this->api->utility->verifyPaymentSignature($attributes);
            
        }
        catch(SignatureVerificationError $e){
            $success = false;
            $error = 'Razorpay Error : ' . $e->getMessage();
            return response()->json([
                    'error' => $error
            ],400);
        }

        if ($success === true)
        {
            $razorpayOrder = $this->api->order->fetch($razorpay_order_id);
            $reciept = $razorpayOrder['receipt'];
            $transaction_id = $razorpay_payment_id;
            
            $order->payment_id = $razorpay_payment_id;
            $order->signature = $razorpay_signature;
            $order->status = 'Verified';
            $order->save();
            
            $transaction = new Transaction();
            $transaction->building_id = $flat->building_id;
            $transaction->user_id = $user->id;
            $transaction->flat_id = $flat->id;
            $transaction->block_id = $flat->block_id;
            $transaction->order_id = $order->order_id;
            $transaction->model = $order->model;
            $transaction->model_id = $order->model_id;
            $transaction->type = 'Credit';
            
            $transaction->payment_type = 'InBank';
            $transaction->amount = $order->amount;
            $transaction->reciept_no = $reciept;
            $event = Event::find($order->model_id);
            $transaction->desc = 'Event Payment for '.$event->name.'  paid by '.$user->name.' through User app .';
            $transaction->status = 'Success';
            $transaction->date = now()->toDateString();
            $transaction->save();
            
            $payment = new Payment();
            $payment->building_id = $flat->building_id;
            $payment->user_id = Auth::User()->id;
            $payment->event_id = $order->model_id;
            $payment->flat_id = $flat->id;
            $payment->type = 'Credit';
$payment->date = Carbon::parse($request->date)->format('Y-m-d');
            $payment->payment_type = 'InBank';
            $payment->amount = $order->amount;
            $payment->status = 'Paid';
            $payment->transaction_id = $transaction->id;
            $payment->save();
            
            
            return response()->json([
                    'message' => 'Payment completed! You can now download or view your reciept'
            ],200);

        }
        else
        {
            return response()->json([
                    'error' => $error
            ],422);
        }
    }
    
    public function get_logo(Request $request)
    {
        $setting = Setting::first();
        $logo = $setting->logo;
        return response()->json([
                'logo' => $logo
        ],200);
    }
    
   public function corpus_fund(Request $request)
{
    $user = Auth::user();
$flat =  AuthHelper::flat();

    $flat = Flat::where('id', $flat->id)
        ->with(['block', 'building', 'owner'])
        ->first();

    // Fetch transactions where model = 'Corpus' and flat_id = current flat
    $transactions = Transaction::where('model', 'Corpus')
        ->where('flat_id', $flat->id)
        ->get();

    return response()->json([
        'flat' => $flat,
        'transactions' => $transactions, // returns [] if no transactions found
    ], 200);
}


    public function society_fund(Request $request)
    {
        $user = Auth::user();
        $flat = $flat = AuthHelper::flat();
        $building = $flat->building;
    
        $transactionsQuery = Transaction::where('building_id', $building->id);
    
        // Filter by model and model_id
        if ($request->filled('model')) {
            $transactionsQuery->where('model', $request->model);
    
            if ($request->filled('model_id')) {
                $transactionsQuery->where('model_id', $request->model_id);
            }
        }
        
         if ($request->filled('payment_type')) {
            $transactionsQuery->where('payment_type', $request->payment_type);
    
            if ($request->filled('payment_type')) {
                $transactionsQuery->where('payment_type', $request->payment_type);
            }
        }
    
        // Filter by from_date and to_date
        if ($request->filled('from_date')) {
            $transactionsQuery->whereDate('date', '>=', $request->from_date);
        }
    
        if ($request->filled('to_date')) {
            $transactionsQuery->whereDate('date', '<=', $request->to_date);
        }
    
        $transactions = $transactionsQuery->get();
    
        // Initialize totals
        $total_debit = 0;
        $total_credit = 0;
        $inhand = 0;
        $inbank = 0;
    
        foreach ($transactions as $transaction) {
            if ($transaction->type == 'Debit') {
                $total_debit += $transaction->amount;
            } elseif ($transaction->type == 'Credit') {
                $total_credit += $transaction->amount;
            }
    
            if ($transaction->payment_type == 'InHand') {
                $inhand += ($transaction->type == 'Credit' ? $transaction->amount : -$transaction->amount);
            } elseif ($transaction->payment_type == 'InBank') {
                $inbank += ($transaction->type == 'Credit' ? $transaction->amount : -$transaction->amount);
            }
        }
    
        return response()->json([
            'transactions' => $transactions->values(),
            'total_debit' => $total_debit,
            'total_credit' => $total_credit,
            'inhand' => $inhand,
            'inbank' => $inbank,
        ], 200);
    }



    public function get_model_data(Request $request)
    {
        $user = Auth::User();
       $flat =  AuthHelper::flat();
        $building = $flat->building;
        $model = $request->model;
        if ($model == 'Event') {
        $data = Event::where('building_id',$building->id)->select(['id', 'name'])->get();
        } elseif ($model == 'Essential') {
            $data = Essential::where('building_id',$building->id)->select(['id', DB::raw('reason as name')])->get();
        }elseif ($model == 'Facility') {
            $data = Facility::where('building_id',$building->id)->select(['id', 'name'])->get();
        } else {
            $data = collect(); // Empty collection if no model matched
        }
        return response()->json([
                'data' => $data
        ],200);
    }
    

public function event_history(Request $request)
{
    $user = Auth::user();
    $flat = AuthHelper::flat();

    $events = Event::where('building_id', $flat->building_id)
    ->where('status', '!=', 'Pending')
        ->with([
            'payments' => function ($paymentQuery) use ($user, $flat) {
                $paymentQuery->whereHas('transaction', function ($q) use ($user, $flat) {
                    $q->where('user_id', $user->id)
                      ->where('flat_id', $flat->id);
                })
                ->with(['transaction' => function ($q) use ($user, $flat) {
                    $q->where('user_id', $user->id)
                      ->where('flat_id', $flat->id)
                      ->with(['user', 'order', 'payerRole']);
                }]);
            },
            // Optional
            // 'payments.transaction'
        ])
        ->get();

    $transactions = $events->pluck('payments')
        ->flatten()
        ->pluck('transaction')
        ->flatten()
        ->unique('id')
        ->values();

    return response()->json([
        'events' => $events,
        'transactions' => $transactions,
        'user' => $user
    ], 200);
}
public function single_event_history(Request $request, )
{
    $user = Auth::user();
    $flat = AuthHelper::flat();

    // Fetch the event with filtered payments + transaction details
    $event = Event::where('id', $request->event_id)
        ->where('building_id', $flat->building_id)   // Ensure user accesses only its building
        ->with([
            'payments' => function ($paymentQuery) use ($user, $flat) {
                $paymentQuery->whereHas('transaction', function ($q) use ($user, $flat) {
                    $q->where('user_id', $user->id)
                      ->where('flat_id', $flat->id);
                })
                ->with(['transaction' => function ($q) use ($user, $flat) {
                    $q->where('user_id', $user->id)
                      ->where('flat_id', $flat->id)
                      ->with(['user', 'order', 'payerRole']);
                }]);
            }
        ])
        ->first();

    // ❌ If event does not exist or not allowed
    if (!$event) {
        return response()->json([
            'message' => 'Event not found or unauthorized',
        ], 404);
    }

    // Extract list of transactions (optional)
    $transactions = $event->payments
        ->pluck('transaction')
        ->flatten()
        ->unique('id')
        ->values();

    return response()->json([
        'event' => $event,
        'transactions' => $transactions,
        'user' => $user
    ], 200);
}


public function essential_history(Request $request)
    {
        $user = Auth::User();
        
    $flat = AuthHelper::flat();
        $essentials = Essential::where('building_id',$flat->building_id)->whereIn('status', ['Active', 'Inactive'])->with(['payments' => function($query) use ($user, $flat) {
             $query->where('user_id', $user->id)->where('flat_id', $flat->id);
        }])->get();
        $flat = Flat::where('owner_id',$user->id)->orWhere('tanent_id',$user->id)->with(['owner','tanent',
        'block:id,name',
        'building:id,user_id,name,gst_no,address'
        ])->first();
        $essentials->map(function ($essential) use ($flat) {
            $essential->flat = $flat;
            return $essential;
        });
        
        foreach($essentials as $essential){
            foreach($essential->payments as $essential_payment){
                $dues_amount = $essential_payment->dues_amount;
                $late_fine = 0;
                $gst = $essential->gst;
                if($essential_payment->status == 'Paid'){
                    $dues_amount = 0;
                    $essential_payment->dues_amount = 0;
                    $essential_payment->save();
                }
                $dueDate = Carbon::parse($essential_payment->essential->due_date);
                if ($essential_payment && $dueDate->lt(now()->startOfDay())) {
                    $late_days = $dueDate->diffInDays(now());
                    if($essential_payment->status == 'Paid'){
                        $late_days = Carbon::parse($essential_payment->date)->diffInDays(Carbon::parse($essential->due_date));
                    }
                    switch ($essential->late_fine_type) {
                        case 'Daily':
                            $late_fine = $late_days * $essential->late_fine_value;
                            break;
                        case 'Fixed':
                            $late_fine = $essential->late_fine_value;
                            break;
                        case 'Percentage':
                            $late_fine = ($essential->dues_amount * $essential->late_fine_value) / 100;
                            break;
                        }
                }
                $total_amount = $dues_amount + $late_fine;
                $total_gst = $total_amount * $gst / 100;
                $grand_total = $total_amount + $total_gst;

                $essential_payment->total_amount = $total_amount;
                $essential_payment->total_gst = $total_gst;
                $essential_payment->grand_total = $grand_total;
                
                   // âœ… Attach transaction if exists
        if ($essential_payment->transaction_id) {
            $transaction = Transaction::find($essential_payment->transaction_id);
            $essential_payment->transaction = $transaction;
        } else {
            $essential_payment->transaction = null;
        }
                
            }
        }
        return response()->json([
                'essentials' => $essentials,
        ],200);
    }
    
    
    public function dnd_mode(Request $request)
    {
        $flat = AuthHelper::flat();
        
        return response()->json([
                'data' => $flat->dnd_mode,
        ],200);
    }
    
    public function update_dnd_mode(Request $request)
    {
        $rules = [
            'dnd_mode' => 'required|in:on,off',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $flat = AuthHelper::flat();
        $flat->dnd_mode = $request->dnd_mode;
        $flat->save();
        return response()->json([
                'data' => $flat->dnd_mode,
        ],200);
    }
    
    public function onboarding(Request $request)
    {
        $setting = Setting::first();
        return response()->json([
                'logo' => $setting->logo,
                'title' => 'Title',
                'text' => 'Lorem Ipsum'
        ],200);
    }
    
    public function create_razorpay_order(Request $request)
    {
        $rules = [
            'package_id' => 'required|numeric',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $package = Package::find($request->package_id);
        
        if(!$package){
            return response()->json([
                'error' => 'Package not found'
            ], 422);
        }
        $subscription = Subscription::where('user_id',$user->id)->where('package_id',$package->id)->where('status','Active')->first();
        if($subscription){
            return response()->json([
                'error' => 'You already have purchased this package'
            ], 422);
        }
        $item_name = $package->name;
        $item_number = $package->id;
        $item_amount = $package->price;

        $orderData = [
            'receipt'         => $item_number,
            'amount'          => $item_amount * 100, // 2000 rupees in paise
            'currency'        => 'INR',
            'payment_capture' => 1 // auto capture
        ];
        
        // $flat = $user->flat;
                $flat=AuthHelper::flat();
        $building = $flat->building;
        if($building->donantion_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        try{
            $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        
            $razorpayOrder = $this->api->order->create($orderData);
        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            $razorpayMessage = $e->getMessage();

            if (strpos($razorpayMessage, 'Amount exceeds maximum amount allowed') !== false) {
                $customMessage = "The amount is too large. Payment not allowed for this amount.";
            } else {
                $customMessage = "We are unable to create a payment. Please contact your building admin.";
            }
        
            return response()->json([
                'error' => $customMessage
            ], 422);
        }
        $razorpayOrderId = $razorpayOrder['id'];
        $displayAmount = $amount = $orderData['amount'];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $url = "https://api.fixer.io/latest?symbols=$this->displayCurrency&base=INR";
            $exchange = json_decode(file_get_contents($url), true);
                    
            $displayAmount = $exchange['rates'][$this->displayCurrency] * $amount / 100;
        }
                    
        $data = [
            "key"               => $building->razorpay_key,
            "amount"            => $amount,
            "name"              => $item_name,
            "description"       => $item_name,
            "prefill"           => [
    			"name"              => $user->name,
    			"email"             => $user->email,
    			"contact"           => $user->phone,
            ],
            "notes"             => [
				"address"           => $user->address,
				"merchant_order_id" => $item_number,
            ],
            "theme"             => [
				"color"             => "#3399cc"
            ],
            "order_id"          => $razorpayOrderId,
        ];
                    
        if ($this->displayCurrency !== 'INR')
        {
            $data['display_currency']  = $this->displayCurrency;
            $data['display_amount']    = $displayAmount;
        }
                    
        $displayCurrency = $this->displayCurrency;
        
        $order = new Order();
        $order->user_id = $user->id;
        $order->package_id = $package->id;
        $order->order_id = $razorpayOrderId;
        $order->status = 'Created';
        $order->save();
        
        return response()->json([
                'data' => $data,
                'displayCurrency' => $displayCurrency
        ],200);
    }
    public function verify_razorpay_signature(Request $request)
    {
        $rules = [
            'razorpay_order_id' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $order = Order::where('order_id',$request->razorpay_order_id)->where('status','Created')->first();
        if(!$order){
            return response()->json([
                    'error' => 'Order id not found',
            ],422);
        }
        $success = true;
        $error = "Payment Failed";
        
        $razorpay_order_id = $request->razorpay_order_id;
        $razorpay_payment_id = $request->razorpay_payment_id;
        $razorpay_signature = $request->razorpay_signature;
        
        // $flat = $user->flat;
                $flat=AuthHelper::flat();
        $building = $flat->building;
        
        if($building->donantion_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
            return response()->json([
                'error' => 'We are unable to create a payment. Please contact your building admin.'
            ], 422);
        }
        $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
        
        // try{
        //     $attributes = array(
        //         'razorpay_order_id' => $razorpay_order_id,
        //         'razorpay_payment_id' => $razorpay_payment_id,
        //         'razorpay_signature' => $razorpay_signature
        //     );
        
        //     $this->api->utility->verifyPaymentSignature($attributes);
            
        // }
        // catch(SignatureVerificationError $e){
        //     $success = false;
        //     $error = 'Razorpay Error : ' . $e->getMessage();
        //     return response()->json([
        //             'error' => $error
        //     ],400);
        // }

        // if ($success === true)
        // {
            $razorpayOrder = $this->api->order->fetch($razorpay_order_id);
            $reciept = $razorpayOrder['receipt'];
            $transaction_id = $razorpay_payment_id;
            
            $order->payment_id = $razorpay_payment_id;
            $order->signature = $razorpay_signature;
            $order->status = 'Verified';
            $order->save();
            
            $subscription = new Subscription();
            $subscription->user_id = $user->id;
            $subscription->package_id = $order->package_id;
            $subscription->status = 'Active';
            $subscription->save();
            
            $package = Package::find($order->package_id);
            
            $info = array(
                'package' => $package
            );
                
            Mail::send('email.package_purchased', ['info' => $info], function ($message) use ($user)
            {
                $message->to($user->email, $user->name)
                ->subject('Package purchased');
            });
            
            $user->create_circle($package->id);
            $user->update_circle_member($package->id);
            
            // $user->wallet = $user->wallet + $package->price;
            // $user->save();
            
            $transaction = new Transaction();
            $transaction->building_id = $flat->building_id;
            $transaction->user_id = $user->id;
            $transaction->type = 'Credit';
            $transaction->reason = $package->name;
            $transaction->amount = $package->price;
            $transaction->balance = $user->wallet;
            $transaction->date = now()->toDateString();
            $transaction->save();
            
            return response()->json([
                    'message' => 'Package purchased successfully'
            ],200);

        // }
        // else
        // {
        //     return response()->json([
        //             'error' => $error
        //     ],422);
        // }
    }
    
    public function get_security_flats(Request $request)
    {
        $user = Auth::User();
        $flats = $user->building->flats;
        return response()->json([
            'flats' => $flats
        ], 200);
    }
    
    public function get_buildings(Request $request)
    {
        $user = Auth::User();
        $guards = Guard::where('user_id',$user->id)->with('building')->get();
        return response()->json([
            'guards' => $guards
        ], 200);
    }
    
    public function select_building(Request $request)
    {
        $rules = [
            'building_id' => 'required|exists:buildings,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $user->building_id = $request->building_id;
        $user->save();
        
        $building = $user->building;
        
        return response()->json([
            'building' => $building,
            'msg' => 'Building selected successfully'
        ], 200);
    }
    
    public function get_gates(Request $request)
    {
        $user = Auth::User();
        $guards = Guard::where('user_id',$user->id)->with(['building','block','gate'])->get();
        return response()->json([
            'guards' => $guards
        ], 200);
    }
    
    public function select_gate(Request $request)
    {
        $rules = [
            'gate_id' => 'required|exists:gates,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $user->gate_id = $request->gate_id;
        $user->save();
        
        $gate = $user->gate->with(['building','block'])->get();
        
        return response()->json([
            // 'gate' => $gate,
            'msg' => 'Gate selected successfully'
        ], 200);
    }
    
    
    public function get_building_flats(Request $request)
    {
        $user = Auth::user();
        $building = $user->gate->building;
    
        $flats = Flat::where('building_id', $building->id)
            ->with(['owner', 'tanent', 'building', 'block'])
            ->withCount([
                'vehicles as two_wheeler_count' => function ($query) {
                    $query->where('vehicle_type', 'two-wheeler')
                          ->whereIn('ownership', ['Own', 'Guest'])
                          ->where(function ($q) {
                              // For Guest, check visitor status
                              $q->where(function ($sub) {
                                  $sub->where('ownership', 'Guest')
                                      ->whereHas('visitor', function ($v) {
                                          $v->where('status', 'Living');
                                      });
                              })
                              // For Own, skip visitor filter
                              ->orWhere('ownership', 'Own');
                          });
                },
                'vehicles as four_wheeler_count' => function ($query) {
                    $query->where('vehicle_type', 'four-wheeler')
                          ->whereIn('ownership', ['Own', 'Guest'])
                          ->where(function ($q) {
                              // For Guest, check visitor status
                              $q->where(function ($sub) {
                                  $sub->where('ownership', 'Guest')
                                      ->whereHas('visitor', function ($v) {
                                          $v->where('status', 'Living');
                                      });
                              })
                              // For Own, skip visitor filter
                              ->orWhere('ownership', 'Own');
                          });
                }
            ])
            ->get();
    
        return response()->json([
            'flats' => $flats
        ], 200);
    }
    
    
    


public function all_flat_vehicles(Request $request)
{
    $user = Auth::user();
    $building = $user->gate->building;

    $flats = Flat::where('building_id', $building->id)
        ->with(['owner', 'tanent', 'building', 'block', 'vehicles'])
        ->withCount([
            'vehicles as two_wheeler_count' => function ($query) {
                $query->where('vehicle_type', 'two-wheeler')
                      ->whereIn('ownership', ['Own', 'Guest'])
                      ->where(function ($q) {
                          $q->where(function ($sub) {
                              $sub->where('ownership', 'Guest')
                                  ->whereHas('visitor', function ($v) {
                                      $v->where('status', 'Living');
                                  });
                          })
                          ->orWhere('ownership', 'Own');
                      });
            },
            'vehicles as four_wheeler_count' => function ($query) {
                $query->where('vehicle_type', 'four-wheeler')
                      ->whereIn('ownership', ['Own', 'Guest'])
                      ->where(function ($q) {
                          $q->where(function ($sub) {
                              $sub->where('ownership', 'Guest')
                                  ->whereHas('visitor', function ($v) {
                                      $v->where('status', 'Living');
                                  });
                          })
                          ->orWhere('ownership', 'Own');
                      });
            }
        ])
        ->get();

    // Filter: skip flats that have NO vehicles
    $filtered = $flats->filter(function ($flat) {
        return count($flat->vehicles) > 0;  // keep only if vehicles exist
    })->values();

    return response()->json([
        'flats' => $filtered
    ], 200);
}


    
public function get_visitorsxxx(Request $request)
{
    $rules = [
        'type' => 'required|in:Planned,Unplanned,Completed,All',
        'fromdate' => 'nullable|date_format:d-m-Y',
        'todate' => 'nullable|date_format:d-m-Y',
        'count' => 'nullable|integer|min:1',
        'page' => 'nullable|integer|min:1',
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first(),
        ], 422);
    }

    $user = Auth::user();
    $flat_id = $user->flat_id;

    // Explicitly filter by flat_id and user_id for safety
    $query = \App\Models\Visitor::with(['gate_passes', 'vehicles', 'inouts'])
        ->where('flat_id', $flat_id)
        ->where('user_id', $user->id);

    // Filter by type/status
    if ($request->type === 'All') {
        $query->whereNotIn('status', ['Completed', 'Expired']);
    } elseif ($request->type === 'Completed') {
        $query->whereIn('status', ['Completed', 'Expired']);
    } else {
        $query->where('type', $request->type)
              ->whereNotIn('status', ['Completed', 'Expired']);
    }

    // Date filters
    if ($request->filled('fromdate')) {
        $from = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromdate)->startOfDay();
        $query->whereDate('created_at', '>=', $from);
    }

    if ($request->filled('todate')) {
        $to = \Carbon\Carbon::createFromFormat('d-m-Y', $request->todate)->endOfDay();
        $query->whereDate('created_at', '<=', $to);
    }

    // Pagination
    $count = $request->input('count');
    $page = $request->input('page', 1);

    if ($count) {
        $visitors = $query->orderBy('created_at', 'desc')->paginate($count, ['*'], 'page', $page);

        return response()->json([
            'visitors' => $visitors->items(),
            'total' => $visitors->total(),
            'current_page' => $visitors->currentPage(),
            'last_page' => $visitors->lastPage(),
            'flat_id' => $flat_id,
        ], 200);
    } else {
        $visitors = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'visitors' => $visitors,
            'total' => $visitors->count(),
            'current_page' => 1,
            'last_page' => 1,
            'flat_id' => $flat_id,
        ], 200);
    }
}

 public function get_visitors(Request $request)
    {
        $rules = [
            'type' => 'required|in:Planned,Unplanned,Completed,All',
            'fromdate' => 'nullable|date_format:d-m-Y',
            'todate' => 'nullable|date_format:d-m-Y',
            'count' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first(),
            ], 422);
        }
    
        $user = Auth::User();
        $flat = AuthHelper::flat();
    
        $query = $flat->visitors()->with(['gate_passes', 'vehicles', 'inouts']);
    
        // Filter by type and status
        if ($request->type === 'All') {
            $query->whereNotIn('status', ['Completed', 'Expired']);
        } elseif ($request->type === 'Completed') {
            $query->whereIN('status', ['Completed','Expired','Deny']);
        } else {
            $query->where('type', $request->type)
                  ->whereNotIn('status', ['Completed', 'Expired']);
        }
    
        // Apply date filters (assuming `created_at` or `date` field exists)
        if ($request->filled('fromdate')) {
            $from = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromdate)->startOfDay();
            $query->whereDate('created_at', '>=', $from);
        }
    
        if ($request->filled('todate')) {
            $to = \Carbon\Carbon::createFromFormat('d-m-Y', $request->todate)->endOfDay();
            $query->whereDate('created_at', '<=', $to);
        }
    
        // Pagination
        $count = $request->input('count');
        $page = $request->input('page', 1);
    
        if ($count) {
            $visitors = $query->orderBy('created_at', 'desc')->paginate($count, ['*'], 'page', $page);
    
            return response()->json([
                'visitors' => $visitors->items(),
                'total' => $visitors->total(),
                'current_page' => $visitors->currentPage(),
                'last_page' => $visitors->lastPage(),
            ], 200);
        } else {
            $visitors = $query->orderBy('created_at', 'desc')->get();
    
            return response()->json([
                'visitors' => $visitors,
                'total' => $visitors->count(),
                'current_page' => 1,
                'last_page' => 1,
            ], 200);
        }
    }
    
 
    
    
    public function get_building_visitors_history(Request $request)
    {
        $rules = [
            'type' => 'required|in:Planned,Unplanned,Completed,All',
        ];
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $building = $user->gate->building;
        
        if($request->type == 'All'){
            $visitors = Visitor::where('building_id',$building->id)->with(['building','flat.block','vehicles','inouts','gate_passes'])->get();
        }else if($request->type == 'Completed'){
            $visitors = Visitor::where('building_id',$building->id)->where('status','Completed')->with(['building','flat.block','vehicles','inouts','gate_passes'])->get();
        }
        else{
            $visitors = Visitor::where('building_id',$building->id)->where('type',$request->type)->with(['building','flat.block','vehicles','inouts','gate_passes'])->get();
        }
        
        return response()->json([
            'visitors' => $visitors
        ], 200);
    }
    
    public function get_building_visitors(Request $request)
    {
        $rules = [
            'type' => 'required|in:Planned,Unplanned,Completed,All',
            'fromdate' => 'nullable|date_format:d-m-Y',
            'todate' => 'nullable|date_format:d-m-Y',
            'count' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ], 422);
        }
    
        $user = Auth::User();
        $building = $user->gate->building;
    
        $query = Visitor::where('building_id', $building->id)
            ->with(['building', 'flat.block', 'vehicles', 'inouts', 'gate_passes']);
    
        // Apply type filter
        if ($request->type !== 'All') {
            if ($request->type === 'Completed') {
                $query->whereIn('status', ['Completed','Expired']);
            } else {
                $query->where('type', $request->type)
                      ->whereNotIn('status', ['Completed', 'Expired']);
            }
        }
    
        // Apply date filters
        if ($request->filled('fromdate')) {
            $from = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromdate)->startOfDay();
            $query->whereDate('created_at', '>=', $from);
        }
    
        if ($request->filled('todate')) {
            $to = \Carbon\Carbon::createFromFormat('d-m-Y', $request->todate)->endOfDay();
            $query->whereDate('created_at', '<=', $to);
        }
    
        $count = $request->input('count');
        $page = $request->input('page', 1);
    
        if ($count) {
            $visitors = $query->orderBy('created_at', 'desc')->paginate($count, ['*'], 'page', $page);
    
            return response()->json([
                'visitors' => $visitors->items(),
                'total' => $visitors->total(),
                'current_page' => $visitors->currentPage(),
                'last_page' => $visitors->lastPage(),
            ], 200);
        } else {
            $visitors = $query->orderBy('created_at', 'desc')->get();
    
            return response()->json([
                'visitors' => $visitors,
                'total' => $visitors->count(),
                'current_page' => 1,
                'last_page' => 1,
            ], 200);
        }
    }

    public function get_building_visitors_gatepass(Request $request)
    {
        $rules = [
            'type' => 'required|in:Planned,Unplanned,Completed,All',
            'fromdate' => 'nullable|date_format:d-m-Y',
            'todate' => 'nullable|date_format:d-m-Y',
            'count' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ], 422);
        }
    
        $user = Auth::User();
        $building = $user->gate->building;
    
    //Approved/Rejected/RejectedExtraItem/ApprovedExtraItem/CheckedOut
    $allowedStatuses = [
    'Approved', //when user create
    
    'Rejected', 
    // 'CheckedOut', 
    
    'ApprovedExtraItem', 
    // 'CheckedOutExtraItem',
    
    'RejectedExtraItem',
    'storedExtraItemSecurity',
    
    // 'ExtraItemGivenUser',
];


        $query = Visitor::where('building_id', $building->id)
            ->whereHas('gate_passes', function ($q) use ($allowedStatuses) {
        $q->whereIn('status', $allowedStatuses);
    })
            ->with(['building', 'flat.block', 'vehicles', 'inouts', 'gate_passes']);
    
    $visitors = $query->get();
        return response()->json([
                'visitors' => $visitors,
            ], 200);
    }
    
    public function get_visitor_details(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
        ];
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        // $flat = $user->flat;
                $flat=AuthHelper::flat();
        $visitor = Visitor::where('id',$request->visitor_id)->where('building_id',$flat->building_id)->with(['building','block','flat','vehicles','inouts','gate_passes'])->first();

        return response()->json([
            'visitor' => $visitor
        ], 200);
    }
    
    public function visitor_details(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
        ];
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $building = $user->gate->building;
        $visitor = Visitor::where('id',$request->visitor_id)->where('building_id',$building->id)->with(['building','flat.block','vehicles','inouts','gate_passes'])->first();
        
        return response()->json([
            'visitor' => $visitor
        ], 200);
    }
    
    public function get_vehicles(Request $request)
{
    $rules = [
        'ownership' => 'required|in:Own,Guest,Visitor,All',
        'flat_id'=>'required',
    ];

    $validation = \Validator::make($request->all(), $rules);
    $error = $validation->errors()->first();
    if ($error) {
        return response()->json([
            'error' => $error
        ], 422);
    }

    $user_id = Auth::user()->id;

    // Get the user's flat_id
    $flatId = $request->flat_id;

    // Get vehicles belonging to the same flat
    if ($request->ownership == 'All') {
        $vehicles = \App\Models\Vehicle::where('flat_id', $flatId)->where('user_id',$user_id)
            ->with(['inouts'])
            ->get();
    } else {
        $vehicles = \App\Models\Vehicle::where('flat_id', $flatId)->where('user_id',$user_id)
            ->where('ownership', $request->ownership)
            ->with(['inouts'])
            ->get();
    }

    return response()->json([
        'vehicles' => $vehicles,
        'ewret'=>$user_id
    ], 200);
}

    
    public function all_vehicles(Request $request)
    {
        $rules = [
            'status' => 'nullable|in:Pending,Approved,Rejected,Completed,All',
            'fromdate' => 'nullable|date_format:d-m-Y',
            'todate' => 'nullable|date_format:d-m-Y',
            'count' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first(),
            ], 422);
        }
    
        $user = Auth::User();
        $building = $user->gate->building;
    
        $query = Vehicle::where('building_id', $building->id)
            ->with(['visitor', 'flat.block', 'flat.building', 'flat.owner', 'flat.tanent', 'inouts'])
            ->whereHas('inouts');
    
        // Filter by status
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }
    
        // Filter by fromdate
        if ($request->filled('fromdate')) {
            $from = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromdate)->startOfDay();
            $query->whereDate('created_at', '>=', $from);
        }
    
        // Filter by todate
        if ($request->filled('todate')) {
            $to = \Carbon\Carbon::createFromFormat('d-m-Y', $request->todate)->endOfDay();
            $query->whereDate('created_at', '<=', $to);
        }
    
        $count = $request->input('count');
        $page = $request->input('page', 1);
    
        if ($count) {
            $vehicles = $query->orderBy('created_at', 'desc')->paginate($count, ['*'], 'page', $page);
    
            return response()->json([
                'vehicles' => $vehicles->items(),
                'total' => $vehicles->total(),
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
            ], 200);
        } else {
            $vehicles = $query->orderBy('created_at', 'desc')->get();
    
            return response()->json([
                'vehicles' => $vehicles,
                'total' => $vehicles->count(),
                'current_page' => 1,
                'last_page' => 1,
            ], 200);
        }
    }

    
    // public function get_building_vehicles(Request $request)
    // {
    //     $rules = [
    //         'ownership' => 'required|in:Own,Guest,Outsider,All',
    //     ];
    
    //     $validation = \Validator::make($request->all(), $rules);
    //     $error = $validation->errors()->first();
    //     if ($error) {
    //         return response()->json([
    //             'error' => $error
    //         ], 422);
    //     }
    //     $user = Auth::User();
    //     $building = $user->gate->building;
    //     if($request->ownership == 'All'){
    //         $vehicles = Vehicle::where('building_id',$building->id)->with(['user', 'flat.owner', 'flat.tanent', 'flat.block', 'building'])->get();
    //         $count = $vehicles->count();
    //     }else{
    //         $vehicles = Vehicle::where('building_id',$building->id)->where('ownership',$request->ownership)->with(['user','flat.owner', 'flat.block', 'flat.tanent','building'])->get();
    //         $count = $vehicles->count();
    //     }
    //     return response()->json([
    //         'vehicles' => $vehicles,
    //         'count' => $count
    //     ], 200);
        
    // }
    
    public function get_building_vehicles(Request $request)
    {
        $rules = [
            'ownership' => 'required|in:Own,Guest,Outsider,All',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ], 422);
        }
    
        $user = Auth::user();
        $building = $user->gate->building;
    
        // Columns to select
        $vehicleSelect = ['id', 'user_id', 'flat_id', 'building_id', 'ownership', 'vehicle_no', 'vehicle_type', 'created_at'];
    
        if ($request->ownership === 'Outsider') {
            $query = Vehicle::where('building_id', $building->id);
        } else {
            $query = Vehicle::where('building_id', $building->id)
                ->with([
                    'user:id,first_name,last_name',
                    'flat:id,name,block_id,building_id,living_status,owner_id,tanent_id',
                    'flat.owner:id,first_name,last_name',
                    'flat.tanent:id,first_name,last_name',
                    'flat.block:id,name',
                    'building:id,name',
                ]);
        }
    
        if ($request->ownership !== 'All') {
            $query->where('ownership', $request->ownership);
        }
    
        $vehicles = $query->get();
    
        // Group by flat_id and clean output
        $grouped = $vehicles->groupBy('flat_id')->map(function ($group) {
            $flat = $group->first()->flat;
    
            $flatData = null;
            if ($flat) {
                $flatData = [
                    'flat_id' => $flat->id,
                    'flat_name' => $flat->name,
                    'block_name' => $flat->block->name,
                    'building_name' => $flat->building->name,
                    'living_status' => $flat->living_status,
                    'person_name' => $flat->living_status === 'Owner'
                        ? $flat->owner->first_name .' '.$flat->owner->last_name
                        : $flat->tanent->first_name .' '.$flat->tanent->last_name,
                ];
            }
    
            // Only keep required vehicle fields
            $vehiclesList = $group->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'vehicle_no' => $vehicle->vehicle_no,
                    'vehicle_type' => $vehicle->vehicle_type,
                    'ownership' => $vehicle->ownership,
                    'driver_name' => $vehicle->driver_name,
                    'phone' => $vehicle->phone,
                    'photo' => $vehicle->photo,
                    'status' => $vehicle->status,
                    'created_at' => $vehicle->created_at->toDateTimeString(),
                    'visitor' => $vehicle->visitor,
                    'inouts' => $vehicle->inouts,
                    
                ];
            });
    
            return [
                'flat' => $flatData,
                'vehicles' => $vehiclesList,
            ];
        })->values();
    
        return response()->json([
            'data' => $grouped,
            'count' => $vehicles->count()
        ], 200);
    }


    
    public function get_flat_vehicles(Request $request)
    {
        $rules = [
            'flat_id' => 'required|exists:flats,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
    
        $user = Auth::user();
        $building = $user->gate->building;
    
        $vehicles = Vehicle::where('flat_id', $request->flat_id)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('ownership', 'Guest')
                        ->whereHas('visitor', function ($v) {
                            $v->where('status', 'Living');
                        });
                })
                ->orWhere('ownership', 'Own');
            })
            ->with(['user', 'flat.owner', 'flat.tanent', 'building'])
            ->get();
    
        return response()->json([
            'vehicles' => $vehicles,
            'count' => $vehicles->count()
        ], 200);
    }

    
    public function create_vehicle(Request $request)
    {
        $rules = [
            'flat_id' => 'nullable|exists:flats,id',
            'visitor_id' => 'nullable|exists:visitors,id',
            'vehicles' => 'nullable|array',
            'vehicles.*.vehicle_type' => 'required_with:vehicles|in:two-wheeler,four-wheeler,auto,other',
            'vehicles.*.vehicle_no' => [
                'required_with:vehicles',
                // 'regex:/^[A-Z]{2}\s?[0-9]{1,2}\s?[A-Z]{1,3}\s?[0-9]{4}$/'
            ],
            'vehicle_type' => 'required_without:vehicles|in:two-wheeler,four-wheeler,auto,other',
            'vehicle_no' => [
                'required_without:vehicles',
                // 'regex:/^[A-Z]{2}\s?[0-9]{1,2}\s?[A-Z]{1,3}\s?[0-9]{4}$/'
            ],
            'ownership' => 'required|in:Own,Guest,Outsider',
            'driver_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'photo' => 'nullable|image',
            'purpose' => 'nullable|string',
            // 'status' => 'nullable|in:Active,Inactive',
                //  'status' => 'nullable|in:Out,In',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ], 422);
        }
    
        // Get flat
        $flat = $request->flat_id ? Flat::find($request->flat_id) : AuthHelper::flat();
        $user = Auth::User();
        $vehicles = [];
    
        // Multiple vehicle creation
        if ($request->filled('vehicles')) {
            foreach ($request->vehicles as $item) {
                $vehicle = new Vehicle();
                $vehicle->vehicle_type = $item['vehicle_type'];
                $vehicle->vehicle_no = $item['vehicle_no'];
                $vehicle->flat_id = $flat->id;
                $vehicle->user_id = $user->id;
                $vehicle->status = $item['status'] ?? 'Active';
                $this->fillVehicleDetails($vehicle, $request, $flat);
                $vehicle->save();
                $vehicles[] = $vehicle;
            }
            return response()->json([
                'vehicles' => $vehicles,
                'msg' => count($vehicles) . ' vehicle(s) created successfully'
            ], 200);
        }
    
        // Single vehicle creation only
        $vehicle = new Vehicle();
        $vehicle->vehicle_type = $request->vehicle_type;
        $vehicle->vehicle_no = $request->vehicle_no;
        $vehicle->flat_id = $flat->id ?? 0;
        $vehicle->user_id = $user->id;
        $vehicle->status = $request->status ?? 'Active';
        $this->fillVehicleDetails($vehicle, $request, $flat);
        $vehicle->save();
    
        return response()->json([
            // 'vehicle' => $vehicle,
            'msg' => 'Vehicle created successfully'
        ], 200);
    }

    public function update_vehicle(Request $request)
    {
        $rules = [
            'vehicle_id' => 'required|exists:vehicles,id',
            'flat_id' => 'nullable|exists:flats,id',
            'visitor_id' => 'nullable|exists:visitors,id',
            'vehicle_type' => 'required|in:two-wheeler,four-wheeler,auto,other',
            'vehicle_no' => [
                'required',
                // 'regex:/^[A-Z]{2}\s?[0-9]{1,2}\s?[A-Z]{1,3}\s?[0-9]{4}$/'
            ],
            'ownership' => 'required|in:Own,Guest,Outsider',
            'driver_name' => 'nullable|string',
            'phone' => 'nullable|string',
            'photo' => 'nullable|image',
            'purpose' => 'nullable|string',
            'status' => 'nullable|in:Out,In',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ], 422);
        }

        // Find the vehicle and verify ownership
        $vehicle = Vehicle::find($request->vehicle_id);
        if (!$vehicle) {
            return response()->json([
                'error' => 'Vehicle not found'
            ], 404);
        }

        // Check if the vehicle belongs to the authenticated user
        $user = Auth::user();
        if ($vehicle->user_id != $user->id) {
            return response()->json([
                'error' => 'You can only update your own vehicles'
            ], 403);
        }

        // Get flat
        $flat = $request->flat_id ? Flat::find($request->flat_id) : AuthHelper::flat();
        
        // Update vehicle details
        $vehicle->vehicle_type = $request->vehicle_type;
        $vehicle->vehicle_no = $request->vehicle_no;
        $vehicle->status = $request->status ?? $vehicle->status ?? 'Active';
        $this->fillVehicleDetails($vehicle, $request, $flat);
        $vehicle->save();
    
        return response()->json([
            'vehicle' => $vehicle,
            'msg' => 'Vehicle updated successfully'
        ], 200);
    }


    // Helper method
    private function fillVehicleDetails($vehicle, $request, $flat)
    {
        if ($request->filled('visitor_id')) {
            $vehicle->visitor_id = $request->visitor_id;
        }
    
        if (in_array($request->ownership, ['Own', 'Visitor'])) {
            $vehicle->flat_id = $flat->id;
            $vehicle->user_id = Auth::id();
            $vehicle->building_id = $flat->building_id;
        }
    
        if (in_array($request->ownership, ['Guest', 'Outsider'])) {
            $user = Auth::user();
            if($flat){
               $vehicle->building_id = $flat->building_id; 
            }else{
                $vehicle->building_id = $user->gate->building->id;
            }
            
        }
    
        $vehicle->ownership = $request->ownership;
    
        if ($request->ownership === 'Outsider') {
            $vehicle->driver_name = $request->driver_name;
            $vehicle->phone = $request->phone;
            $vehicle->purpose = $request->purpose;
        }
    
        // if ($request->hasFile('photo')) {
        //     $file = $request->file('photo');
        //     $extension = $file->getClientOriginalExtension();
        //     $filename = 'images/vehicles/' . uniqid() . '.' . $extension;
    
        //     // Delete old photo if exists and vehicle is being updated
        //     if (!empty($vehicle->photo)) {
        //         Storage::disk('s3')->delete($vehicle->photo);
        //     }
    
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $vehicle->photo = $filename;
        // }
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($vehicle->photo_filename)) {
                $file_path = public_path('images/' . $vehicle->photo_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'vehicles/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/vehicles/'), $filename);
            $vehicle->photo = $filename;
        }
    }

    
    public function delete_vehicle(Request $request)
    {
        $rules = [
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        $user = Auth::User();
        $vehicle = Vehicle::where('user_id',$user->id)->where('id',$request->vehicle_id)->withTrashed()->first();
        if(!$vehicle){
            return response()->json([
                'msg' => 'You dont have proper permission to delete this vehicle'
            ],422);
        }
        if($request->type == 'delete'){
            $msg = 'Vehicle deleted successfully';
            $vehicle->delete();
        }else{
            $msg = 'Vehicle restored successfully';
            $vehicle->restore();
        }
        
        return response()->json([
                'msg' => $msg
        ],200);
    }
    
    
    
    //UP Visitor 1
    public function create_unplanned_visitor(Request $request)
    {
        $rules = [
            'total_members' => 'required|integer|min:1',
            'head_name' => 'required',
            'head_phone' => 'required',
            'head_photo' => 'nullable|image',
            'stay_from' => 'required',
            'stay_to' => 'required',
            'visiting_purpose' => 'required',
            'type' => 'required|in:Unplanned',
            'stay_option' => 'required|in:Daily,Hourly',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validation->errors()->first()
            ], 422);
        }
        
        $user = Auth::User();
        if($request->flat_id){
            $flat = Flat::find($request->flat_id);
            if($flat->dnd_mode == 'on'){
                return response()->json([
                    'error' => 'DND mode is enabled for this, you cant create unplanned visitor'
                ], 422);
            }
        }
        $visitor = new Visitor();

        if ($request->hasFile('head_photo')) {
            $file = $request->file('head_photo');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($visitor->head_photo_filename)) {
                $file_path = public_path('images/' . $visitor->head_photo_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'visitors/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/visitors/'), $filename);
            $visitor->head_photo = $filename;
        }
        
        $code = mt_rand(100000, 999999);
        if($flat->tanent_id > 0){
            $visitor->user_id = $flat->tanent_id;
        }else{
            $visitor->user_id = $flat->owner_id;
        }
        $visitor->user_id = $user->id;
        $visitor->security_id = Auth::Id();
        
        $visitor->building_id = $flat->building_id;
        $visitor->flat_id = $flat->id;
        $visitor->block_id = $flat->block_id;
        $visitor->total_members = $request->total_members;
        $visitor->head_name = $request->head_name;
        $visitor->head_phone = $request->head_phone;
        $visitor->stay_from = $request->stay_from;
        $visitor->stay_to = $request->stay_to;
        $visitor->visiting_purpose = $request->visiting_purpose;
        $visitor->type = $request->type;
        $visitor->stay_option = $request->stay_option;
        $visitor->code = $code;
        
        $visitor->save();
        
        if(!$flat){
            return response()->json([
                'visitor' => $visitor,
                'msg' => 'Visitor added successfully'
            ],200);
        }
if (in_array($flat->living_status, ['Owner', 'Vacant'])) {
    $user = $flat->owner;
} else {
    $user = $flat->tanent;
}



        $title = 'Visitor Approval Needed for '.$visitor->head_name;
        $body = 'Security has raised an unplanned visitor request. Please respond with an action.';
        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'Visitors',
            
            'params' => json_encode([
            'ScreenTab' => 'Unplanned Visitors',
            'visitor_id' => $visitor->id,
            
            'user_id' => $user->id,
            'flat_id' => $flat->id,
            'building_id' =>$flat->building_id,
            ]),
            'categoryId' => 'UnplannedVisitorsReq',
            'channelId' => 'longring',
            'ios_sound' => 'longring.wav',
            'type' => 'PARCEL_CREATED',
        ];

        
        
    // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $user->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                // 'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ],
            ['user']
    );
        
        return response()->json([
                'visitor' => $visitor,
                'msg' => 'Visitor added successfully',
                // '$user'=>$user
                'notification_status' => $notificationResult
        ],200);
    }
    
    //UP Visitor 3 // updated 14-Nov-2025 9AM
    public function visitor_in_out(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
            'type' => 'required|in:In,Out',
            'purpose' => 'required',
            'code' => 'required'
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $visitor = Visitor::where('id',$request->visitor_id)->whereIn('status',['Living','AllowIn'])->first();
        if(!$visitor){
            return response()->json([
                'error' => 'Living Visitor not found'
            ], 422);
        }
        if($visitor->code != $request->code){
            return response()->json([
                'error' => 'Invalid visitor code'
            ], 422);
        }
        $visitor_in_out = new VisitorInout();
        $visitor_in_out->flat_id = $visitor->flat_id;
        $visitor_in_out->building_id = $visitor->building_id;
        $visitor_in_out->user_id = $visitor->user_id;
        $visitor_in_out->visitor_id = $request->visitor_id;
        $visitor_in_out->type = $request->type;
        $visitor_in_out->purpose = $request->purpose;
        $visitor_in_out->code = $request->code;
        $visitor_in_out->save();
        
        $visitor->current_status=$request->type;

        $visitor->status = 'Living';
        $visitor->security_id=Auth::user()->id;
        $visitor->save();
        
        $flat = $visitor->flat;
        
        if(!$flat){
            return response()->json([
                'msg' => 'Visitor status updated successfully'
            ], 200);
        }
        if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
        
        if($visitor->type == 'Planned'){
            $screentab = 'Planned Visitors';
            $categoryId = 'PlannedVisitors';
        }else{
            $screentab = 'Unplanned Visitors';
            $categoryId = 'UnplannedVisitors';
        }
        
        
        $title = 'Visitor '.$request->type;
        $body = 'Visitor '.$visitor->head_name.' has checked '.$request->type;
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'Visitors',
            'params' => json_encode([
             'ScreenTab' => $screentab,
            'visitor_id' => $visitor->id,
            'user_id' => $user->id,
            'flat_id' => $flat->id,
            'building_id' =>$flat->building_id,
            ]),
            'categoryId' => $categoryId,
            'channelId' => 'bellnotificationsound',
            'sound' => 'bellnotificationsound.wav',
            'type' => 'UNPLANNED_VISITOR_COMPLETED',
        ];
        
        
    // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $user->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ]
    );
        

        
 



        
        return response()->json([
            'visitor_in_out' => $visitor_in_out,
            'msg' => 'Visitor status updated successfully',
            'ds'=>$dataPayload,
            '$title'=>$title,
            '$body'=>$body,
            '$notificationResult'=>$notificationResult
        ], 200);
    }
    
    //UP Visitor 4
    public function complete_visitor_journey(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
            'code' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $visitor = Visitor::find($request->visitor_id);
        if($visitor->status == 'Completed'){
            return response()->json([
                'error' => 'Visitor journey is already completed'
            ], 422);
        }
        if($visitor->status == 'Pending'){
            return response()->json([
                'error' => 'Yet visitor journey is not started'
            ], 422);
        }
        $visitor->status = 'Completed';
        $visitor->save();
        
        $visitor_in_out = new VisitorInout();
        $visitor_in_out->flat_id = $visitor->flat_id;
        $visitor_in_out->building_id = $visitor->building_id;
        $visitor_in_out->user_id = $visitor->user_id;
        $visitor_in_out->visitor_id = $request->visitor_id;
        $visitor_in_out->type = 'Out';
        $visitor_in_out->purpose = 'Mission Completed';
        $visitor_in_out->code = $request->code;
        $visitor_in_out->save();
        
        
        $flat = $visitor->flat;
        
        if(!$flat){
            return response()->json([
                'msg' => 'Visitor status updated successfully'
            ], 200);
        }
         if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
   
           $title = 'Visitor Checked Out';
        $body = 'Visitor #['.$visitor->head_name.'] has checked out from the premises.';
        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'Visitors',
            'params' => json_encode([
                'ScreenTab' => 'Completed',
                'visitor_id' => $visitor->id,
                'user_id'=>$user->id,
                'flat_id'=>$flat->id,
               'building_id'=> $flat->building_id
                ]),
            'categoryId' => 'UnplannedVisitors',
            'channelId' => 'bellnotificationsound',
            'sound' => 'bellnotificationsound.wav',
            'type' => 'UNPLANNED_VISITOR_COMPLETED',
            'actionButtons' => json_encode(["Allow", "Deny", "Stay at Lobby"]),
        ];
        
            $notificationResult = NotificationHelper::sendNotification(
        $user->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ]
    );

        // foreach ($devices as $device) {
        //     $token = $device->fcm_token;
        //     $type = strtolower($device->device_type); // ios, android, web
    
        //     if (in_array($type, ['android', 'web'])) {
        //         $message = CloudMessage::withTarget('token', $token)
        //             ->withNotification(Notification::create(
        //                 $title,
        //                 $body
        //             ))
        //             ->withData($dataPayload);
    
        //         try {
        //             $firebaseMessaging->send($message);
        //             // \Log::info("Firebase notification sent to: $token");
        //         } catch (\Exception $e) {
        //             \Log::error("FCM error for token $token: " . $e->getMessage());
        //         }
    
        //     } elseif ($type === 'ios') {
        //         $alert = Alert::create()
        //             ->setTitle($title)
        //             ->setBody($body);
        //         $payload = Payload::create()
        //             ->setAlert($alert)
        //             ->setSound('bellnotificationsound.wav')
        //             ->setCustomValue('click_action', $dataPayload['click_action'])
        //             ->setCustomValue('screen', $dataPayload['screen'])
        //             ->setCustomValue('params', $dataPayload['params'])
        //             ->setCustomValue('categoryId', $dataPayload['categoryId'])
        //             ->setCustomValue('channelId', $dataPayload['channelId'])
        //             ->setCustomValue('sound', $dataPayload['sound'])
        //             ->setCustomValue('type', $dataPayload['type'])
        //             ->setCustomValue('user_id', $dataPayload['user_id'])
        //             ->setCustomValue('flat_id', $dataPayload['flat_id'])
        //             ->setCustomValue('building_id', $dataPayload['building_id']);
        //         $notification = new ApnsNotification($payload, $token);

        //         try {
        //             $apnsClient->addNotification($notification);
        //             $responses = $apnsClient->push(); // returns an array of ApnsResponseInterface
        //             foreach ($responses as $response) {
        //                 if ($response->getStatusCode() === 200) {
        //                     // Push was successful
        //                 } else {
        //                     // Push failed, optionally log the error
        //                     \Log::error('APNs Error', [
        //                         'status' => $response->getStatusCode(),
        //                         'reason' => $response->getReasonPhrase(),
        //                         'error'  => $response->getErrorReason()
        //                     ]);
        //                 }
        //             }
        //         } catch (\Exception $e) {
        //             \Log::error("APNs exception: " . $e->getMessage());
        //         }
        //     }
        // }
        
        return response()->json([
            'msg' => 'Visitor status updated successfully'
        ], 200);
    }
    
    
    public function get_todays_completed_visitors(Request $request)
    {
        $startOfToday = Carbon::today();
        $endOfToday = Carbon::today()->endOfDay();
        
        $building = Auth::user()->gate->building;
        
        $visitors = Visitor::where('building_id', $building->id)
            ->where('status', 'Completed')
            ->whereBetween('updated_at', [$startOfToday, $endOfToday])
            ->with(['building', 'flat.block', 'vehicles', 'inouts', 'gate_passes'])
            ->get();
        
        return response()->json([
            'visitors' => $visitors
        ], 200);
    }
        
    public function vehicle_in_out(Request $request)
    {
        $rules = [
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|in:In,Out',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }

        $vehicle = Vehicle::find($request->vehicle_id);
        $vehicle->status = $request->type;
        $vehicle->save();
        $vehicle_in_out = new VehicleInout();
        $vehicle_in_out->vehicle_id = $request->vehicle_id;
        $vehicle_in_out->building_id = $vehicle->building_id;
        $vehicle_in_out->type = $request->type;
        $vehicle_in_out->save();
    
        return response()->json([
            'vehicle_in_out' => $vehicle_in_out,
            'msg' => 'Vehicle status updated successfully'
        ], 200);
    }
    
    public function visitor_in_out_history(Request $request)
    {
          Log::info('visitor_in_out_history >>'.$request->visitor_id.'visitor_id');
        if($request->visitor_id){
            $visitor_inouts = Auth::User()->gate->building->visitor_inouts()->where('visitor_id',$request->visitor_id)->with(['visitor','building','flat.block'])->get();
        }
        else{
            $visitor_inouts = Auth::User()->gate->building->visitor_inouts()->with(['visitor','building','flat.block'])->get();
        }
        
        return response()->json([
            'visitor_inouts' => $visitor_inouts
        ], 200);
    }
    
    public function vehicle_in_out_history(Request $request)
    {
        $rules = [
            'vehicle_id' => 'required|exists:vehicles,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $building = Auth::User()->gate->building;
        $vehicle = Vehicle::find($request->vehicle_id);
        $vehicle_inouts = VehicleInout::where('vehicle_id',$request->vehicle_id)->get();
        return response()->json([
            'vehicle' => $vehicle,
            'vehicle_inouts' => $vehicle_inouts,
        ], 200);
    }
    
    public function search_flat(Request $request)
    {
        $user = Auth::User();
        $building = $user->gate->building;
        $flat = Flat::where('building_id',$building->id)->where('name',$request->flat_no)->first();
        return response()->json([
                'flat' => $flat
        ],200);
    }
    
    public function create_parcel(Request $request)
    {
        $rules = [
            'name' => 'required',
            'flat_id' => 'required|exists:flats,id',
            'photo' => 'nullable|image',
            'delivery_guy_name' => 'required',
            'delivery_guy_phone' => 'required',
            'purpose_of_visit' => 'required',
            'stay_time' => 'required',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $user = Auth::User();
        $gate = $user->gate;
        $guard = Guard::where('user_id',$user->id)->where('gate_id',$user->gate_id)->first();
        if(!$guard){
            return response()->json([
                'error' => 'Guard not found'
            ], 422);
        }
        if($request->flat_id){
            $flat = Flat::find($request->flat_id);
        }else{
            $flat = $user->flat;
        }
        $parcel = new Parcel();

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($parcel->photo_filename)) {
                $file_path = public_path('images/' . $parcel->photo_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'parcels/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/parcels/'), $filename);
            $parcel->photo = $filename;
        }
        $code = mt_rand(100000, 999999);
        if($flat->tanent_id){
            $parcel->user_id = $flat->tanent_id;
        }else{
            $parcel->user_id = $flat->owner_id;
        }
        $parcel->building_id = $flat->building_id;
        $parcel->flat_id = $flat->id;
        $parcel->guard_id = $user->id;
        $parcel->gate_id = $guard->gate_id;
        $parcel->name = $request->name;
        $parcel->delivery_guy_name = $request->delivery_guy_name;
        $parcel->delivery_guy_phone = $request->delivery_guy_phone;
        $parcel->purpose_of_visit = $request->purpose_of_visit;
        $parcel->stay_time = $request->stay_time;
    
        $parcel->code = $code;
        $parcel->status = 'OnGoing';
        if($flat->dnd_mode == 'on'){
            $parcel->status = 'LeaveInGate';
        }
        $parcel->save();
        
       if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'My Parcels',
            'params' => json_encode([
            'ScreenTab' => 'Incoming Parcels',
            'parcel_id' => $parcel->id,
            'user_id' => $user->id,
            'flat_id' => $flat->id,
            'building_id' =>$flat->building_id,
            ]),
            'categoryId' => 'ParcelRequestMess',
            'channelId' => 'longring',
            'sound' => 'longring.wav',
            'type' => 'PARCEL_CREATED',

        ];
        $title = 'New Parcel Delivery';
        $body = 'You have a delivery: ['.$parcel->name .'] by ['.$parcel->delivery_guy_name.']. Please respond: Allow, Leave at Security, or Deny.';
 
 
         $notificationResult = NotificationHelper::sendNotification(
            $user->id,
            $title,
            $body,
            $dataPayload,
            [
                'from_id' =>$user->id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ]
        );
        
        return response()->json([
                'parcel' => $parcel,
                'msg' => 'Parcel added successfully',
        ],200);
    }
    
    public function parcel_handover_to_owner(Request $request)
    {
        $rules = [
            'parcel_id' => 'required|exists:parcels,id',
            'code' => 'required|exists:parcels,code',
        ];
        
        $parcel = Parcel::find($request->parcel_id);
        if($parcel->code != $request->code){
            return response()->json([
                'msg' => 'Invalid parcel code'
            ],200);
        }
        $parcel->status = 'Delivered';
        $parcel->save();
        
         $flat = $parcel->flat;
      if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
        $devices = DB::table('user_devices')
            ->where('user_id', $user->id)
            ->whereNotNull('fcm_token')
            ->where('is_active', 1)
            ->select('fcm_token', 'device_type')
            ->get();
    
        // Setup Firebase for Android/Web
        $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
        $firebaseMessaging = $firebaseFactory->createMessaging();
    
        $apnsClient = $this->apnsClient;
        if($parcel->status == 'Delivered')
        {
            $title = 'Parcel Delivered to resident';
            $body = 'The parcel #['.$parcel->name.'] has been delivered.';
            
            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'My Parcels',
                'params' => json_encode(['ScreenTab' => 'Delivered Parcels', 'parcel_id' => $parcel->id]),
                'categoryId' => 'ParcelStatusUpdates',
                'channelId' => 'Parcel',
                'sound' => 'bellnotificationsound.wav',
                'type' => 'PARCEL_DELIVERED',
                'user_id' => (string)$user->id,
                'flat_id' => (string)$flat->id,
                'flat_id' => (string)$flat->block_id,
                'building_id' => (string)$flat->building_id,
            ];
        }
        
        
        foreach ($devices as $device) {
            $token = $device->fcm_token;
            $type = strtolower($device->device_type); // ios, android, web
    
            if (in_array($type, ['android', 'web'])) {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification(Notification::create(
                        $title,
                        $body
                    ))
                    ->withData($dataPayload);
    
                try {
                    $firebaseMessaging->send($message);
                    // \Log::info("Firebase notification sent to: $token");
                } catch (\Exception $e) {
                    \Log::error("FCM error for token $token: " . $e->getMessage());
                }
    
            } elseif ($type === 'ios') {
                $alert = Alert::create()
                    ->setTitle($title)
                    ->setBody($body);
                $payload = Payload::create()
                    ->setAlert($alert)
                    ->setSound('bellnotificationsound.wav')
                    ->setCustomValue('click_action', $dataPayload['click_action'])
                    ->setCustomValue('screen', $dataPayload['screen'])
                    ->setCustomValue('params', $dataPayload['params'])
                    ->setCustomValue('categoryId', $dataPayload['categoryId'])
                    ->setCustomValue('channelId', $dataPayload['channelId'])
                    ->setCustomValue('sound', $dataPayload['sound'])
                    ->setCustomValue('type', $dataPayload['type'])
                    ->setCustomValue('user_id', $dataPayload['user_id'])
                    ->setCustomValue('flat_id', $dataPayload['flat_id'])
                    ->setCustomValue('building_id', $dataPayload['building_id']);
                $notification = new ApnsNotification($payload, $token);
    
                try {
                    $apnsClient->addNotification($notification);
                    $responses = $apnsClient->push(); // returns an array of ApnsResponseInterface
                    foreach ($responses as $response) {
                        if ($response->getStatusCode() === 200) {
                            // Push was successful
                        } else {
                            // Push failed, optionally log the error
                            \Log::error('APNs Error', [
                                'status' => $response->getStatusCode(),
                                'reason' => $response->getReasonPhrase(),
                                'error'  => $response->getErrorReason()
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("APNs exception: " . $e->getMessage());
                }
            }
        }
        return response()->json([
                'parcel' => $parcel,
                'msg' => 'Parcel delivered successfully'
        ],200);
        
        
    }
    
    
    public function resend_recieve_request(Request $request)
    {
        $rules = [
            'parcel_id' => 'required|exists:parcels,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $parcel = Parcel::find($request->parcel_id);
        $flat = $parcel->flat;
        //       if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
        //     $user = $flat->owner;
        // }else{
        //     $user = $flat->tanent;
        // }
        
        if (in_array($flat->living_status, ['Owner', 'Vacant'])) {
    $user = $flat->owner;
} else {
    $user = $flat->tanent;
}


$title = 'Parcel Re-delivery Notification';
$body = 'Your parcel ' . $parcel->name . ' by ' . $parcel->delivery_guy_name . 
    ' has been resent. Please respond to confirm or provide updated delivery instructions.';

$dataPayload = [
    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
    'screen' => 'My Parcels',
    'params' => json_encode([
        'ScreenTab' => 'Incoming Parcels',
        'parcel_id' => $parcel->id,
        'user_id' => $user->id,
        'flat_id' => $flat->id,
        'building_id' => $flat->building_id,
    ]),
    'categoryId' => 'ParcelResponse',
                    'channelId' => 'longring',
                'sound' => 'longring.wav',
    'type' => 'PARCEL_CREATED',
];

$notificationResult = NotificationHelper::sendNotification(
    $user->id, // Recipient user
    $title,
    $body,
    $dataPayload,
    [
        'from_id' => $user->id,
        'flat_id' => $flat->id,
        'building_id' => $flat->building_id,
        'type' => 'parcel_created',
        'apns_client' => $this->apnsClient ?? null,
        'ios_sound' => 'bellnotificationsound.wav',
    ]
);

        
        return response()->json([
                'msg' => 'Recieve request resent successfully'
        ],200);
    
    }
    
    public function get_building_parcels(Request $request)
    {
        $rules = [
            'status' => 'nullable|in:OnGoing,AllowIn,CheckIn,LeaveInGate,StoredInSecurity,Delivered,Deny',
            'fromdate' => 'nullable|date_format:d-m-Y',
            'todate' => 'nullable|date_format:d-m-Y',
            'count' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first(),
            ], 422);
        }
    
        $user = Auth::User();
    
        $query = Parcel::where('building_id', $user->gate->building_id)
            ->with(['flat.block.building', 'flat.owner', 'flat.tanent']);
    
        // Filter by status
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }
    
        // Filter by fromdate
        if ($request->filled('fromdate')) {
            $from = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromdate)->startOfDay();
            $query->whereDate('created_at', '>=', $from);
        }
    
        // Filter by todate
        if ($request->filled('todate')) {
            $to = \Carbon\Carbon::createFromFormat('d-m-Y', $request->todate)->endOfDay();
            $query->whereDate('created_at', '<=', $to);
        }
    
        $count = $request->input('count');
        $page = $request->input('page', 1);
    
        if ($count) {
            $parcels = $query->orderBy('created_at', 'desc')->paginate($count, ['*'], 'page', $page);
    
            return response()->json([
                'parcels' => $parcels->items(),
                'total' => $parcels->total(),
                'current_page' => $parcels->currentPage(),
                'last_page' => $parcels->lastPage(),
            ], 200);
        } else {
            $parcels = $query->orderBy('created_at', 'desc')->get();
    
            return response()->json([
                'parcels' => $parcels,
                'total' => $parcels->count(),
                'current_page' => 1,
                'last_page' => 1,
            ], 200);
        }
    }

    
    public function get_flat_parcels(Request $request)
    {
        $parcels = Parcel::where('flat_id',$request->flat_id)->with(['flat.block.building'])->get();
        return response()->json([
                'parcels' => $parcels
        ],200);
    }
    
    public function get_my_parcelsxxx(Request $request)
{
    $rules = [
        'status' => 'required|in:OnGoing,ToRecieve,Recieved,All',
        'fromdate' => 'nullable|date_format:d-m-Y',
        'todate' => 'nullable|date_format:d-m-Y',
        'count' => 'nullable|integer|min:1',
        'page' => 'nullable|integer|min:1',
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first(),
        ], 422);
    }

    $user = Auth::user();
    $query = $user->parcels();
    $flat_id=$user->flat_id;
    
    // Filter by status
    if ($request->status !== 'All') {
        $query->where('status', $request->status);
    }

    // Date filter
    if ($request->filled('fromdate')) {
        $from = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromdate)->startOfDay();
        $query->whereDate('created_at', '>=', $from);
    }

    if ($request->filled('todate')) {
        $to = \Carbon\Carbon::createFromFormat('d-m-Y', $request->todate)->endOfDay();
        $query->whereDate('created_at', '<=', $to);
    }

    // Pagination
     // Check for count param to decide if pagination is needed
    if ($request->filled('count')) {
        $count = (int) $request->input('count', 10);
        $page = (int) $request->input('page', 1);

        $parcels = $query->paginate($count, ['*'], 'page', $page);

        return response()->json([
            'parcels' => $parcels->items(),
            'total' => $parcels->total(),
            'current_page' => $parcels->currentPage(),
            'last_page' => $parcels->lastPage(),
                 'flat_id'=>$flat_id
        ], 200);
    } else {
        // Return all if no pagination
        $parcels = $query->get();
        return response()->json([
            'parcels' => $parcels,
            'total' => $parcels->count(),
            'current_page' => 1,
            'last_page' => 1,
       
        ], 200);
    }
}


public function get_my_parcels(Request $request)
{
    $rules = [
        'status' => 'required|in:OnGoing,ToRecieve,Recieved,All',
        'fromdate' => 'nullable|date_format:d-m-Y',
        'todate' => 'nullable|date_format:d-m-Y',
        'count' => 'nullable|integer|min:1',
        'page' => 'nullable|integer|min:1',
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first(),
        ], 422);
    }

    $user = Auth::user();
    $flat_id = AuthHelper::flat()->id;

    // Start query from user's parcels, but ensure same flat_id too
    $query = \App\Models\Parcel::where('flat_id', $flat_id)
        ->where('user_id', $user->id);

    // Filter by status
    if ($request->status !== 'All') {
        $query->where('status', $request->status);
    }

    // Date filters
    if ($request->filled('fromdate')) {
        $from = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromdate)->startOfDay();
        $query->whereDate('created_at', '>=', $from);
    }

    if ($request->filled('todate')) {
        $to = \Carbon\Carbon::createFromFormat('d-m-Y', $request->todate)->endOfDay();
        $query->whereDate('created_at', '<=', $to);
    }

    // Pagination
    if ($request->filled('count')) {
        $count = (int) $request->input('count', 10);
        $page = (int) $request->input('page', 1);

        $parcels = $query->paginate($count, ['*'], 'page', $page);

        return response()->json([
            'parcels' => $parcels->items(),
            'total' => $parcels->total(),
            'current_page' => $parcels->currentPage(),
            'last_page' => $parcels->lastPage(),
            'flat_id' => $flat_id,
        ], 200);
    } else {
        // Return all if no pagination
        $parcels = $query->get();

        return response()->json([
            'parcels' => $parcels,
            'total' => $parcels->count(),
            'current_page' => 1,
            'last_page' => 1,
            'flat_id' => $flat_id,
        ], 200);
    }
}



    public function update_parcel_status(Request $request)
    {
        $rules = [
            'parcel_id' => 'required|exists:parcels,id',
            'status' => 'required|in:Recieved,Not Recieved'
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $user = Auth::User();
        $parcel = Parcel::where('id',$request->parcel_id)->where('user_id',$user->id)->first();
        if(!$parcel){
            return response()->json([
                'error' => 'Parcel not found'
            ], 422);
        }
        $parcel->status = $request->status;
        $parcel->save();
        
        $flat = $parcel->flat;
        $building = $parcel->building;
        $guards = $building->guards;
        
        foreach($guards as $guard)
        {
            $user = $guard->user;
            $devices = DB::table('user_devices')
                ->where('user_id', $user->id)
                ->whereNotNull('fcm_token')
                ->where('is_active', 1)
                ->select('fcm_token', 'device_type')
                ->get();
        
            // Setup Firebase for Android/Web
            $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
            $firebaseMessaging = $firebaseFactory->createMessaging();
        
            $apnsClient = $this->apnsClient;
            if($parcel->status == 'Recieved'){
                $title = 'Parcel Recieved by Resident';
                $body = 'User of ['.$flat->name.'] '.$flat->block->name. '] has recieved the delivery. Please proceed with delivered-returned';
                $dataPayload = [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'screen' => 'My Parcels',
                    'params' => json_encode(['ScreenTab' => 'Incoming Parcels','parcel_id' => $parcel->id]),
                    'categoryId' => 'ParcelResponse',
                    'channelId' => 'Parcel',
                    'sound' => 'bellnotificationsound.wav',
                    'type' => 'PARCEL_RECIEVED',
                    'user_id' => (string)$user->id,
                    'flat_id' => (string)$flat->id,
                    'building_id' => (string)$flat->building_id,
                ];
            }
            
            if($parcel->status == 'Not Recieved'){
                $title = 'Parcel did not recieved by Resident';
                $body = 'User of ['.$flat->name.'] '.$flat->block->name. '] has not recieved the delivery. Please search the delivery person.';
                $dataPayload = [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'screen' => 'My Parcels',
                    'params' => json_encode(['parcel_id' => $parcel->id]),
                    'categoryId' => 'ParcelResponse',
                    'channelId' => 'Parcel',
                    'sound' => 'bellnotificationsound.wav',
                    'type' => 'PARCEL_NOT_RECIEVED',
                    'user_id' => (string)$user->id,
                    'flat_id' => (string)$flat->id,
                    'flat_id' => (string)$flat->block_id,
                    'building_id' => (string)$flat->building_id,
                ];
            }
            if($parcel->status == 'LeaveInGate'){
                $title = 'Leave at Gate Confirmed';
                $body = 'User of '.$flat->name.' '.$flat->block->name. ' requested the parcel be left at the gate. Please store it safely.';
                $dataPayload = [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'screen' => 'My Parcels',
                    'params' => json_encode(['parcel_id' => $parcel->id]),
                    'categoryId' => 'Parcelstore',
                    'channelId' => 'Parcel',
                    'sound' => 'bellnotificationsound.wav',
                    'type' => 'PARCEL_LEAVE_AT_GATE',
                    'user_id' => (string)$user->id,
                    'flat_id' => (string)$flat->id,
                    'flat_id' => (string)$flat->block_id,
                    'building_id' => (string)$flat->building_id,
                ];
            }

            foreach ($devices as $device) {
                $token = $device->fcm_token;
                $type = strtolower($device->device_type); // ios, android, web
        
                if (in_array($type, ['android', 'web'])) {
                    $message = CloudMessage::withTarget('token', $token)
                        ->withNotification(Notification::create(
                            $title,
                            $body
                        ))
                        ->withData($dataPayload);
        
                    try {
                        $firebaseMessaging->send($message);
                        // \Log::info("Firebase notification sent to: $token");
                    } catch (\Exception $e) {
                        \Log::error("FCM error for token $token: " . $e->getMessage());
                    }
        
                } elseif ($type === 'ios') {
                    $alert = Alert::create()
                        ->setTitle($title)
                        ->setBody($body);
                    $payload = Payload::create()
                        ->setAlert($alert)
                        ->setSound('bellnotificationsound.wav')
                        ->setCustomValue('click_action', $dataPayload['click_action'])
                        ->setCustomValue('screen', $dataPayload['screen'])
                        ->setCustomValue('params', $dataPayload['params'])
                        ->setCustomValue('categoryId', $dataPayload['categoryId'])
                        ->setCustomValue('channelId', $dataPayload['channelId'])
                        ->setCustomValue('sound', $dataPayload['sound'])
                        ->setCustomValue('type', $dataPayload['type'])
                        ->setCustomValue('user_id', $dataPayload['user_id'])
                        ->setCustomValue('flat_id', $dataPayload['flat_id'])
                        ->setCustomValue('building_id', $dataPayload['building_id']);
                    $notification = new ApnsNotification($payload, $token);
        
                    try {
                        $apnsClient->addNotification($notification);
                        $responses = $apnsClient->push(); // returns an array of ApnsResponseInterface
                        foreach ($responses as $response) {
                            if ($response->getStatusCode() === 200) {
                                // Push was successful
                            } else {
                                // Push failed, optionally log the error
                                \Log::error('APNs Error', [
                                    'status' => $response->getStatusCode(),
                                    'reason' => $response->getReasonPhrase(),
                                    'error'  => $response->getErrorReason()
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error("APNs exception: " . $e->getMessage());
                    }
                }
            }
        }
        return response()->json([
                'parcel' => $parcel,
                'msg' => 'Parcel status updated successfully'
        ],200);
        
    }
    
    public function take_parcel_action(Request $request)
    {
        $rules = [
            'parcel_id' => 'required|exists:parcels,id',
            'action' => 'required|in:AllowIn,CheckIn,DeliveredReturned,LeaveInGate,StoredInSecurity,GivenToUser,Deny,ReturnedRejected'
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $user = Auth::User();
        $parcel = Parcel::where('id',$request->parcel_id)->where('user_id',$user->id)->first();
        $parcel->status = $request->action;
        $parcel->save();
        
        $flat = $parcel->flat;
        $building = $parcel->building;
        $guards = $building->guards;
        $guard_id=$parcel->guard_id;
        
        $flat = Flat::where('id', $flat->id)
            ->with(['owner', 'tanent', 'building', 'block'])
            ->withCount([
                'vehicles as two_wheeler_count' => function ($query) {
                    $query->where('vehicle_type', 'two-wheeler')
                          ->whereIn('ownership', ['Own', 'Guest'])
                          ->where(function ($q) {
                              // For Guest, check visitor status
                              $q->where(function ($sub) {
                                  $sub->where('ownership', 'Guest')
                                      ->whereHas('visitor', function ($v) {
                                          $v->where('status', 'Living');
                                      });
                              })
                              // For Own, skip visitor filter
                              ->orWhere('ownership', 'Own');
                          });
                },
                'vehicles as four_wheeler_count' => function ($query) {
                    $query->where('vehicle_type', 'four-wheeler')
                          ->whereIn('ownership', ['Own', 'Guest'])
                          ->where(function ($q) {
                              // For Guest, check visitor status
                              $q->where(function ($sub) {
                                  $sub->where('ownership', 'Guest')
                                      ->whereHas('visitor', function ($v) {
                                          $v->where('status', 'Living');
                                      });
                              })
                              // For Own, skip visitor filter
                              ->orWhere('ownership', 'Own');
                          });
                }
            ])
            ->first();
            
            
            
if ($parcel->status == 'AllowIn') {
    $title = 'Parcel Approved by Resident';
    $body = 'User of [' . $flat->name . '] ' . $flat->block->name . ' has allowed the delivery. Please proceed with check-in';

    $dataPayload = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'screen' => 'SelectedFlatDeliveryList',
        'params' => json_encode([
            'parcel_id' => $parcel->id,
             'item' => $flat,
            'user_id' => $guard_id,
            'flat_id' => $flat->id,
            'building_id' =>$flat->building_id,
        ]),
        'categoryId' => 'ParcelResponse',
        'channelId' => 'Parcel',
        'sound' => 'bellnotificationsound.wav',
        'type' => 'PARCEL_ALLOWED',
    ];

    $notificationResult = NotificationHelper::sendNotification(
        $guard_id,
        $title,
        $body,
        $dataPayload,
        [
            'from_id' => $guard_id,
            'flat_id' => $flat->id,
            'building_id' => $flat->building_id,
            'type' => 'parcel_allowed',
            'apns_client' => $this->apnsClient ?? null,
            'ios_sound' => 'bellnotificationsound.wav',
        ]
    );
}

if ($parcel->status == 'Deny') {
    $title = 'Parcel Denied by Resident';
    $body = 'User of [' . $flat->name . '] ' . $flat->block->name . ' has denied the delivery. Please return it to the delivery person.';

    $dataPayload = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'screen' => 'SelectedFlatDeliveryList',
        'params' => json_encode([
            'parcel_id' => $parcel->id,
             'item' => $flat,
            'user_id' => $guard_id,
            'flat_id' => $flat->id,
            'building_id' =>$flat->building_id,
        ]),
        'categoryId' => 'ParcelResponse',
        'channelId' => 'Parcel',
        'sound' => 'bellnotificationsound.wav',
       'type' => 'PARCEL_DENIED',
    ];
       
    $notificationResult = NotificationHelper::sendNotification(
        $guard_id,
        $title,
        $body,
        $dataPayload,
        [
            'from_id' => $guard_id,
            'flat_id' => $flat->id,
            'building_id' => $flat->building_id,
            'type' => 'parcel_denied',
            'apns_client' => $this->apnsClient ?? null,
            'ios_sound' => 'bellnotificationsound.wav',
        ]
    );
}

if ($parcel->status == 'LeaveInGate') {
    $title = 'Leave at Gate Confirmed';
    $body = 'User of [' . $flat->name . '] ' . $flat->block->name . ' requested the parcel be left at the gate. Please store it safely.';

    $dataPayload = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'screen' => 'SelectedFlatDeliveryList',
        'params' => json_encode([
            'parcel_id' => $parcel->id,
            
            'user_id' => $guard_id,
            'flat_id' => $flat->id,
            'building_id' =>$flat->building_id,
        ]),
        'categoryId' => 'Parcelstore',
        'channelId' => 'Parcel',
        'sound' => 'bellnotificationsound.wav',
        'type' => 'PARCEL_LEAVE_AT_GATE',
        'user_id' => (string)$user->id,
        'flat_id' => (string)$flat->id,
        'building_id' => (string)$flat->building_id,
    ];

    $notificationResult = NotificationHelper::sendNotification(
        $guard_id,
        $title,
        $body,
        $dataPayload,
        [
            'from_id' => $guard_id,
            'flat_id' => $flat->id,
            'building_id' => $flat->building_id,
           'type' => 'parcel_leave_at_gate',
            'apns_client' => $this->apnsClient ?? null,
            'ios_sound' => 'bellnotificationsound.wav',
        ]
    );
}

            
            
                // $title = 'Parcel Approved by Resident';
                // $body = 'User of ['.$flat->name.'] '.$flat->block->name. '] has allowed the delivery. Please proceed with check-in';
                // $dataPayload = [
                //     'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                //     'screen' => 'SelectedFlatDeliveryList',
                //     'params' => json_encode(['item' => $flat]),
                //     'categoryId' => 'ParcelResponse',
                //     'channelId' => 'Parcel',
                //     'sound' => 'bellnotificationsound.wav',
                //     'type' => 'PARCEL_ALLOWED',
                //     'user_id' => (string)$user->id,
                //     'flat_id' => (string)$flat->id,
                //     'building_id' => (string)$flat->building_id,
                // ];
        
        
        // foreach($guards as $guard)
        // {
        //     $user = $guard->user;
        //     $devices = DB::table('user_devices')
        //         ->where('user_id', $user->id)
        //         ->whereNotNull('fcm_token')
        //         ->where('is_active', 1)
        //         ->select('fcm_token', 'device_type')
        //         ->get();
        
        //     // Setup Firebase for Android/Web
        //     $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
        //     $firebaseMessaging = $firebaseFactory->createMessaging();
        
        //     $apnsClient = $this->apnsClient;
        //     if($parcel->status == 'AllowIn'){
        //         $title = 'Parcel Approved by Resident';
        //         $body = 'User of ['.$flat->name.'] '.$flat->block->name. '] has allowed the delivery. Please proceed with check-in';
        //         $dataPayload = [
        //             'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //             'screen' => 'SelectedFlatDeliveryList',
        //             'params' => json_encode(['item' => $flat]),
        //             'categoryId' => 'ParcelResponse',
        //             'channelId' => 'Parcel',
        //             'sound' => 'bellnotificationsound.wav',
        //             'type' => 'PARCEL_ALLOWED',
        //             'user_id' => (string)$user->id,
        //             'flat_id' => (string)$flat->id,
        //             'building_id' => (string)$flat->building_id,
        //         ];
        //     }
            
        //     if($parcel->status == 'Deny'){
        //         $title = 'Parcel Denied by Resident';
        //         $body = 'User of ['.$flat->name.'] '.$flat->block->name. '] has denied the delivery. Please return it to the delivery person.';
        //         $dataPayload = [
        //             'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //             'screen' => 'SelectedFlatDeliveryList',
        //             'params' => json_encode(['item' => $flat]),
        //             'categoryId' => 'ParcelDenied',
        //             'channelId' => 'Parcel',
        //             'sound' => 'bellnotificationsound.wav',
        //             'type' => 'PARCEL_DENIED',
        //             'user_id' => (string)$user->id,
        //             'flat_id' => (string)$flat->id,
        //             'flat_id' => (string)$flat->block_id,
        //             'building_id' => (string)$flat->building_id,
        //         ];
        //     }
        //     if($parcel->status == 'LeaveInGate'){
        //         $title = 'Leave at Gate Confirmed';
        //         $body = 'User of ['.$flat->name.'] '.$flat->block->name. '] requested the parcel be left at the gate. Please store it safely.';
        //         $dataPayload = [
        //             'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //             'screen' => 'My Parcels',
        //             'params' => json_encode(['parcel_id' => $parcel->id]),
        //             'categoryId' => 'Parcelstore',
        //             'channelId' => 'Parcel',
        //             'sound' => 'bellnotificationsound.wav',
        //             'type' => 'PARCEL_LEAVE_AT_GATE',
        //             'user_id' => (string)$user->id,
        //             'flat_id' => (string)$flat->id,
        //             'flat_id' => (string)$flat->block_id,
        //             'building_id' => (string)$flat->building_id,
        //         ];
        //     }

        //     foreach ($devices as $device) {
        //         $token = $device->fcm_token;
        //         $type = strtolower($device->device_type); // ios, android, web
        
        //         if (in_array($type, ['android', 'web'])) {
        //             $message = CloudMessage::withTarget('token', $token)
        //                 ->withNotification(Notification::create(
        //                     $title,
        //                     $body
        //                 ))
        //                 ->withData($dataPayload);
        
        //             try {
        //                 $firebaseMessaging->send($message);
        //                 // \Log::info("Firebase notification sent to: $token");
        //             } catch (\Exception $e) {
        //                 \Log::error("FCM error for token $token: " . $e->getMessage());
        //             }
        
        //         } elseif ($type === 'ios') {
        //             $alert = Alert::create()
        //                 ->setTitle($title)
        //                 ->setBody($body);
        //             $payload = Payload::create()
        //                 ->setAlert($alert)
        //                 ->setSound('bellnotificationsound.wav')
        //                 ->setCustomValue('click_action', $dataPayload['click_action'])
        //                 ->setCustomValue('screen', $dataPayload['screen'])
        //                 ->setCustomValue('params', $dataPayload['params'])
        //                 ->setCustomValue('categoryId', $dataPayload['categoryId'])
        //                 ->setCustomValue('channelId', $dataPayload['channelId'])
        //                 ->setCustomValue('sound', $dataPayload['sound'])
        //                 ->setCustomValue('type', $dataPayload['type'])
        //                 ->setCustomValue('user_id', $dataPayload['user_id'])
        //                 ->setCustomValue('flat_id', $dataPayload['flat_id'])
        //                 ->setCustomValue('building_id', $dataPayload['building_id']);
        //             $notification = new ApnsNotification($payload, $token);
        
        //             try {
        //                 $apnsClient->addNotification($notification);
        //                 $responses = $apnsClient->push(); // returns an array of ApnsResponseInterface
        //                 foreach ($responses as $response) {
        //                     if ($response->getStatusCode() === 200) {
        //                         // Push was successful
        //                     } else {
        //                         // Push failed, optionally log the error
        //                         \Log::error('APNs Error', [
        //                             'status' => $response->getStatusCode(),
        //                             'reason' => $response->getReasonPhrase(),
        //                             'error'  => $response->getErrorReason()
        //                         ]);
        //                     }
        //                 }
        //             } catch (\Exception $e) {
        //                 \Log::error("APNs exception: " . $e->getMessage());
        //             }
        //         }
        //     }
        // }
        return response()->json([
                'parcel' => $parcel,
                'msg' => 'Parcel status updated successfully'
        ],200);
    }
    
    public function update_security_parcel_status(Request $request)
    {
        $rules = [
            'parcel_id' => 'required|exists:parcels,id',
            'status' => 'required|in:AllowIn,CheckIn,DeliveredReturned,LeaveInGate,StoredInSecurity,GivenToUser,Deny,ReturnedRejected',
            'code' => 'required_if:status,GivenToUser'
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $user = Auth::User();
        $guard = Guard::where('gate_id',$user->gate_id)->where('user_id',$user->id)->first();
        $parcel = Parcel::where('id',$request->parcel_id)->where('guard_id',$user->id)->first();
        if($request->status == 'GivenToUser'){
            if($request->code != $parcel->code){
                return response()->json([
                    'error' => 'Invalid code'
                ], 422);
            }
        }
        $parcel->status = $request->status;
        $parcel->save();
        
        $flat = $parcel->flat;
        if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
        
        
        switch ($parcel->status) {
    case 'CheckIn':
        $title = 'Delivery Checked In';
        $body = 'The delivery person has been checked in for parcel [' . $parcel->name . '].';
        $type = 'PARCEL_CHECKED_IN';
        $screen = 'My Parcels';
        $screenTab = 'Incoming Parcels';
        break;

    case 'DeliveredReturned':
        $title = 'Parcel Delivered';
        $body = 'The parcel #[' . $parcel->name . '] has been delivered and the delivery person has exited.';
        $type = 'PARCEL_DELIVERED_RETURNED';
        $screen = 'My Parcels';
        $screenTab = 'Parcels History';
        break;

    case 'StoredInSecurity':
        $title = 'Parcel Stored at Security';
        $body = 'The parcel #[' . $parcel->name . '] has been stored at the security desk.';
        $type = 'PARCEL_STORED_IN_SECURITY';
        $screen = 'My Parcels';
        $screenTab = 'Collect Parcels';
        break;

    case 'GivenToUser':
        $title = 'Parcel Handed Over';
        $body = 'The parcel #[' . $parcel->name . '] has been handed over to the user.';
        $type = 'PARCEL_GIVEN_TO_USER';
        $screen = 'My Parcels';
        $screenTab = 'Parcels History';
        break;

    case 'ReturnedRejected':
        $title = 'Parcel Returned to Delivery Person';
        $body = 'The parcel #[' . $parcel->name . '] has been returned after user rejection.';
        $type = 'PARCEL_RETURNED_REJECTED';
        $screen = 'My Parcels';
        $screenTab = 'Parcels History';
        break;

    default:
        return; // no matching status
}

$dataPayload = [
    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
    'screen' => $screen,
    'params' => json_encode([
        'ScreenTab' => $screenTab ?? null,
        'parcel_id' => $parcel->id,
        'user_id' => $user->id,
        'flat_id' => $flat->id,
        'building_id' => $flat->building_id,
    ]),
    'categoryId' => 'ParcelStatusUpdates',
    'channelId' => 'bellnotificationsound',
    'sound' => 'bellnotificationsound.wav',
    'type' => $type,
];

$notificationResult = NotificationHelper::sendNotification(
    $user->id,
    $title,
    $body,
    $dataPayload,
    [
        'from_id' => $user->id,
        'flat_id' => $flat->id,
        'building_id' => $flat->building_id,
        'type' => strtolower($type),
        'apns_client' => $this->apnsClient ?? null,
        'ios_sound' => 'bellnotificationsound.wav',
    ]
);

        
        
        
        // $devices = DB::table('user_devices')
        //     ->where('user_id', $user->id)
        //     ->whereNotNull('fcm_token')
        //     ->where('is_active', 1)
        //     ->select('fcm_token', 'device_type')
        //     ->get();
    
        // // Setup Firebase for Android/Web
        // $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
        // $firebaseMessaging = $firebaseFactory->createMessaging();
    
        // $apnsClient = $this->apnsClient;
        // if($parcel->status == 'CheckIn')
        // {
        //     $title = 'Delivery Checked In';
        //     $body = 'The delivery person has been checked in for parcel ['.$parcel->name.'].';
        //     $dataPayload = [
        //         'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //         'screen' => 'My Parcels',
        //         'params' => json_encode(['ScreenTab' => 'Incoming Parcels', 'parcel_id' => $parcel->id]),
        //         'categoryId' => '',
        //         'channelId' => 'bellnotificationsound',
        //         'sound' => 'bellnotificationsound.wav',
        //         'type' => 'PARCEL_CHECKED_IN',
        //         'user_id' => (string)$user->id,
        //         'flat_id' => (string)$flat->id,
        //         'flat_id' => (string)$flat->block_id,
        //         'building_id' => (string)$flat->building_id,
        //     ];
        // }
        // if($parcel->status == 'DeliveredReturned')
        // {
        //     $title = 'Parcel Delivered';
        //     $body = 'The parcel #['.$parcel->name.'] has been delivered and the delivery person has exited.';
        //     $dataPayload = [
        //         'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //         'screen' => 'My Parcels',
        //         'params' => json_encode(['ScreenTab' => 'Delivered Parcels', 'parcel_id' => $parcel->id]),
        //         'categoryId' => '',
        //   'channelId' => 'bellnotificationsound',
        //         'sound' => 'bellnotificationsound.wav',
        //         'type' => 'PARCEL_DELIVERED_RETURNED',
        //         'user_id' => (string)$user->id,
        //         'flat_id' => (string)$flat->id,
        //         'flat_id' => (string)$flat->block_id,
        //         'building_id' => (string)$flat->building_id,
        //     ];
        // }
        // if($parcel->status == 'StoredInSecurity')
        // {
        //     $title = 'Parcel Stored at Security';
        //     $body = 'The parcel #['.$parcel->name.'] has been stored at the security desk.';
        //     $dataPayload = [
        //         'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //         'screen' => 'My Parcels',
        //         'params' => json_encode(['ScreenTab' => 'Incoming Parcels', 'parcel_id' => $parcel->id]),
        //         'categoryId' => 'ParcelStatusUpdates',
        //   'channelId' => 'bellnotificationsound',
        //         'sound' => 'bellnotificationsound.wav',
        //         'type' => 'PARCEL_STORED_IN_SECURITY',
        //         'user_id' => (string)$user->id,
        //         'flat_id' => (string)$flat->id,
        //         'flat_id' => (string)$flat->block_id,
        //         'building_id' => (string)$flat->building_id,
        //     ];
        // }
        // if($parcel->status == 'GivenToUser')
        // {
        //     $title = 'Parcel Handed Over';
        //     $body = 'The parcel #['.$parcel->name.'] has been handed over to the user.';
            
        //     $dataPayload = [
        //         'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //         'screen' => 'My Parcels',
        //         'params' => json_encode(['ScreenTab' => 'Delivered Parcels', 'parcel_id' => $parcel->id]),
        //         'categoryId' => 'ParcelStatusUpdates',
        //              'channelId' => 'bellnotificationsound',
        //         'sound' => 'bellnotificationsound.wav',
        //         'type' => 'PARCEL_GIVEN_TO_USER',
        //         'user_id' => (string)$user->id,
        //         'flat_id' => (string)$flat->id,
        //         'flat_id' => (string)$flat->block_id,
        //         'building_id' => (string)$flat->building_id,
        //     ];
        // }
        // if($parcel->status == 'ReturnedRejected')
        // {
        //     $title = 'Parcel Returned to Delivery Person';
        //     $body = 'The parcel #['.$parcel->name.'] has been returned after user rejection.';
            
        //     $dataPayload = [
        //         'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //         'screen' => 'My Parcels',
        //         'params' => json_encode(['ScreenTab' => 'Delivered Parcels', 'parcel_id' => $parcel->id]),
        //         'categoryId' => 'ParcelStatusUpdates',
        //             'channelId' => '',
        //         'sound' => 'bellnotificationsound.wav',
        //         'type' => 'PARCEL_RETURNED_REJECTED',
        //         'user_id' => (string)$user->id,
        //         'flat_id' => (string)$flat->id,
        //         'flat_id' => (string)$flat->block_id,
        //         'building_id' => (string)$flat->building_id,
        //     ];
        // }
        
        
        // foreach ($devices as $device) {
        //     $token = $device->fcm_token;
        //     $type = strtolower($device->device_type); // ios, android, web
    
        //     if (in_array($type, ['android', 'web'])) {
        //         $message = CloudMessage::withTarget('token', $token)
        //             ->withNotification(Notification::create(
        //                 $title,
        //                 $body
        //             ))
        //             ->withData($dataPayload);
    
        //         try {
        //             $firebaseMessaging->send($message);
        //             // \Log::info("Firebase notification sent to: $token");
        //         } catch (\Exception $e) {
        //             \Log::error("FCM error for token $token: " . $e->getMessage());
        //         }
    
        //     } elseif ($type === 'ios') {
        //         $alert = Alert::create()
        //             ->setTitle($title)
        //             ->setBody($body);
        //         $payload = Payload::create()
        //             ->setAlert($alert)
        //             ->setAlert($alert)
        //             ->setSound('bellnotificationsound.wav')
        //             ->setCustomValue('click_action', $dataPayload['click_action'])
        //             ->setCustomValue('screen', $dataPayload['screen'])
        //             ->setCustomValue('params', $dataPayload['params'])
        //             ->setCustomValue('categoryId', $dataPayload['categoryId'])
        //             ->setCustomValue('channelId', $dataPayload['channelId'])
        //             ->setCustomValue('sound', $dataPayload['sound'])
        //             ->setCustomValue('type', $dataPayload['type'])
        //             ->setCustomValue('user_id', $dataPayload['user_id'])
        //             ->setCustomValue('flat_id', $dataPayload['flat_id'])
        //             ->setCustomValue('building_id', $dataPayload['building_id']);
        //         $notification = new ApnsNotification($payload, $token);
    
        //         try {
        //             $apnsClient->addNotification($notification);
        //             $responses = $apnsClient->push(); // returns an array of ApnsResponseInterface
        //             foreach ($responses as $response) {
        //                 if ($response->getStatusCode() === 200) {
        //                     // Push was successful
        //                 } else {
        //                     // Push failed, optionally log the error
        //                     \Log::error('APNs Error', [
        //                         'status' => $response->getStatusCode(),
        //                         'reason' => $response->getReasonPhrase(),
        //                         'error'  => $response->getErrorReason()
        //                     ]);
        //                 }
        //             }
        //         } catch (\Exception $e) {
        //             \Log::error("APNs exception: " . $e->getMessage());
        //         }
        //     }
        // }
        
        
        
        return response()->json([
                'parcel' => $parcel,
                'msg' => 'Parcel status updated successfully'
        ],200);
    }
    
    public function take_visitor_action(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
            'action' => 'required|in:AllowIn,LeaveInGate,Deny'
        ];
        
        // $user=
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $visitor = Visitor::find($request->visitor_id);
        $visitor->status = $request->action;
        $visitor->save();
        
        // $flat = $visitor->flat;
        // $building = $flat->building;
        // $guards = $building->guards;
        
        $security_id=$visitor->security_id;
        // $user = Auth::User();
        // $guard = Guard::where('user_id',$user->id)->first();
        
        
        // Determine display text for status
$displayStatus = ($visitor->status === "LeaveInGate") ? "Lobby Stay" : $visitor->status;
        
        $title = 'Visitor Action Response Sent';
      $body = 'User has responded with [' . $displayStatus . '] for visitor #[' . $visitor->head_name . '].';
        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'VisitorEntryDetails',
            'params' => json_encode([
            'ScreenTab' => 'Unplanned Visitors',
            'visitor_id' => $visitor->id,
            ]),
                    'categoryId' => 'UnplannedVisitors',
                    'channelId' => 'security',
                    'sound' => 'bellnotificationsound.wav',
                    'type' => 'UNPLANNED_VISITOR_USER_RESPONDED',
                    // 'flat_id' => (string)$visitor->flat_id,
                    // 'building_id' => (string)$flat->building_id,
        ];

        
        
    // // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $security_id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $security_id,  // From the person accepting
                'flat_id' => $visitor->flat_id,
                'building_id' => $visitor->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ],
            ['security']
    );
        
        
        return response()->json([
                // 'visitor' => $visitor,
                'msg' => 'Visitor status updated successfully',
                // 'notificationResult'=>$notificationResult
        ],200);
        
    }
    
    
    // department routes

public function department_profile(Request $request)
{
    $user = Auth::user();

    // Get the department's building user ID
    $building_user_id = AuthHelperForIssue::department();

    // Fetch the building user along with their role
    $building_user = BuildingUser::with('role')->find($building_user_id);

    if (!$building_user) {
        return response()->json([
            'error' => 'Department not found'
        ], 404);
    }

    return response()->json([
        'user' => $user,
        'role_name' => $building_user->role ? $building_user->role->name : null
    ], 200);
}

    
public function my_departments(Request $request)
{
    $rules = [
        'type' => 'required|exists:roles,type',
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first()
        ], 422);
    }

    $user = Auth::user();

$departments = BuildingUser::where('user_id', $user->id)
    ->whereHas('role', function ($q) use ($request) {
        $q->where('type', $request->type);
    })
    ->with(['role', 'building:id,name'])
    ->get();

    return response()->json([
        'departments' => $departments
    ], 200);
}

    
    public function select_department(Request $request)
    {
        $rules = [
            'department_id' => 'required|exists:building_users,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        
        $user = Auth::User();
        $tokenId = $user->token()->id;
        
            // Update the token with the selected flat_id
       DB::table('oauth_access_tokens')
        ->where('id', $tokenId)
        ->update(['department_id' => $request->department_id]);
        
        // $user->department_id = $request->department_id;
        
        $user->save();
        return response()->json([
                'msg' => 'Department selected successfully'
        ],200);
    }
    
    //Role Issues Step 1 
    public function get_issues(Request $request)
    {
        $rules = [
            'status' => 'required|in:Pending,Ongoing,Completed,On Hold',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $user = Auth::User();
        
        $department_id=AuthHelperForIssue::department();
        if($department_id){
            $department=BuildingUser::where('id',$department_id)->with('role')->first();
        }
        
        $departmentNew = $user->department;
        
        $jkbvfjkd="kjsbd";
        
        if($department->role->type == 'issue'){
            if($request->status == 'Ongoing' || $request->status == 'On Hold'||$request->status == 'Completed'||$request->status == 'Rejected'||$request->status == 'Solved'){
                $issues = Issue::where('building_id',$department->building_id)->where('role_id',$department->role_id)->where('status',$request->status)->where('role_user_id',$user->id)
                ->with(['flat','block','building','user','building:id,name','photos','department','comments.user','comments.replies.user'])->orderBy('id', 'DESC')->get();
            }
            else{
            $issues = Issue::where('building_id',$department->building_id)->where('role_id',$department->role_id)->where('status',$request->status)
            ->with(['flat','block','building','user','building:id,name','photos','department','comments.user','comments.replies.user'])->orderBy('id', 'DESC')->get();
            }
        }else{
            if($request->status == 'Pending'){
                $issues = Issue::where('building_id',$department->building_id)->where('role_id',$department->role_id)->where('status',$request->status)
                ->with(['flat','block','building','user','building:id,name','photos','department','comments.user','comments.replies.user'])->orderBy('id', 'DESC')->get();
            }
            else{
                $issues = Issue::where('building_id',$department->building_id)->where('role_user_id',$user->id)->where('role_id',$department->role_id)->where('status',$request->status)
            ->with(['flat','block','building','user','building:id,name','photos','department','comments.user','comments.replies.user'])->orderBy('id', 'DESC')->get();
            }
        }
        
        return response()->json([
            '$jkbvfjkd'=>$jkbvfjkd,
                'issues' => $issues,
        ],200);
    }
    
   //Role Issues Step 2
   public function accept_issue(Request $request)
    {
    $rules = [
        'issue_id' => 'required|exists:issues,id',
    ];

    $validation = \Validator::make($request->all(), $rules);
    $error = $validation->errors()->first();
    if ($error) {
        return response()->json([
            'error' => $error
        ], 422);
    }
    
    // Find the issue
    $issue = Issue::where('id', $request->issue_id)
        ->where('status', 'Pending')
        ->first();
        
    if (!$issue) {
        return response()->json([
            'error' => 'Issue not found or already accepted by someone else'
        ], 404);
    }
    
    // Get the current authenticated user (the one accepting the issue)
    $acceptedBy = Auth::User();
    
if ((int)$issue->user_id === (int)$acceptedBy->id) {
    return response()->json([
        'msg' => 'The issue creator cannot accept their own issue.',
    ], 409);
}

    
    // Update issue status
    $issue->role_user_id = $acceptedBy->id;
    $issue->status = 'Ongoing';
    $issue->save();
    
    
    // Send notification to the issue creator (not to yourself)
    if ($issue->user_id && $issue->user_id != $acceptedBy->id) {
        // Load the relationship to get the acceptor's name
        $issue->load('role_user_id');
        
        $title = 'Issue Accepted';
        $body = $acceptedBy->name . ' has accepted your issue and is now working on it.';
        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'Raise an Issue',
            'params' => json_encode([
                'ScreenTab' => 'Pending Issue',
                'status' => 'Ongoing',
                'issue_id' => $issue->id,
                 'user_id' => $issue->user_id,
                 'flat_id' => $issue->flat_id,
                 'building_id' => $issue->building_id,
            ]),
            'categoryId' => 'IssueUpdate',
            'channelId' => 'default',
            'sound' => 'default',
            'type' => 'ISSUE_ACCEPTED',
        ];
        
        // Send notification using helper
        $notificationResult = NotificationHelper::sendNotification(
            $issue->user_id,  // Send to issue creator
            $title,
            $body,
            $dataPayload,
            [
                'from_id' => $acceptedBy->id,  // From the person accepting
                'flat_id' => $issue->flat_id,
                'building_id' => $issue->building_id,
                // 'role_id'=>$issue->role_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'default'
            ],
            ["user"]
        );
    }

    
    return response()->json([
        'msg' => 'Issue accepted successfully',
    ], 200);
}

   //Role Issues Step 3
    public function get_issue_comments(Request $request)
    {
    $LoginedUserId=Auth::User()->id;
    // ✅ 1. Validation
    $rules = [
        'issue_id' => 'required|exists:issues,id',
    ];

    $validation = \Validator::make($request->all(), $rules);
    if ($validation->fails()) {
        return response()->json([
            'status' => 'error',
            'error' => $validation->errors()->first(),
        ], 422);
    }
    
     $issueRaiseUserID = Issue::where('id', $request->issue_id)->first()->user_id;

    // ✅ 2. Fetch comments with related users
$comments = Comment::where('issue_id', $request->issue_id)
    ->with([
        'user:id,first_name,last_name,photo,gender',
        'role_user:id,first_name,last_name,photo,gender'
    ])
    ->orderBy('created_at', 'asc')
    ->get();

//   dd($comments);
    

        
        // dd($comments->user);

    // ✅ 3. Response
    return response()->json([
        'status' => 'success',
        'issue_id' => $request->issue_id,
        'comments_count' => $comments->count(),
        'comments' => $comments,
        'loginedUserId'=>$LoginedUserId,
        'issueRaiseUserID'=>$issueRaiseUserID,
    ], 200);
}

   //Role Issues Step 4
    //add_comment api for both. We can find in user apis
    
    //Role Issues Step 5
    public function update_issue_status(Request $request)
    {
        $rules = [
            'status' => 'required|in:Pending,Ongoing,Rejected,Completed,On Hold',
            'issue_id' => 'required|exists:issues,id',
            'reason' => 'nullable|string',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $issue = Issue::find($request->issue_id);
        $issue->status = $request->status;
        $issue->reason = $request->reason;
        $issue->save();
        
           $user = Auth::User();
           
// Get the current user name
$userName = $user->name;

// Set title and body based on status
if ($request->status == "Ongoing") {
    $title = 'Issue Status Updated: On Process';
    $body = 'The issue is now marked as On Process by ' . $userName . '.';
    $ScreenTab='Pending Issue';

} elseif ($request->status == "Completed") {
    $title = 'Issue Status Updated: Completed';
    $body = 'The issue has been marked as Completed by ' . $userName . '.';
    $ScreenTab='Solved';

} elseif ($request->status == "Rejected") {
    $title = 'Issue Status Updated: Rejected';
    $body = 'The issue has been rejected by ' . $userName . '. Reason: ' . $request->reason . '.';
    $ScreenTab='Pending Issue';

}
elseif ($request->status == "On Hold"||$request->status == "On Hold") {
    $title = 'Issue Status Updated: On Hold';
    $body = 'The issue is now marked as On Hold by ' . $userName . '.';
    $ScreenTab='Pending Issue';
}
else {
    // Optional fallback for unexpected statuses
    $title = 'Issue Status Updated';
    $body = 'The issue status has been updated by ' . $userName . '.';
}

$dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'Raise an Issue',
            'params' => json_encode([
                'ScreenTab' => $ScreenTab,
                'issue_id' => $issue->id,
                 'user_id' => $issue->user_id,
                 'flat_id' => $issue->flat_id,
                 'building_id' => $issue->building_id,
                ]),
            'categoryId' => 'issue-update',
            'type' => 'ISSUES_COMMENT',
        ];
        
        
        if( $issue->user_id){
            // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $issue->user_id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $issue->user_id,  // From the person accepting
                'flat_id' => $issue->flat_id,
                'building_id' => $issue->building_id,
                'type' => 'ISSUES_COMMENT',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'default'
            ],['user']
    );
        
        }
        
        return response()->json([
                'issue' => $issue,
                'msg' => 'Issue status updated',
                // '$notificationResult'=>$notificationResult,
                // ' $issue->user_id,'=> $issue->user_id,
        ],200);
    }

   //Role Issues Step 6
    public function issue_history(Request $request)
    {
        $user = Auth::User();
        // $department = $user->department;
        
    $department_id=AuthHelperForIssue::department();
        if($department_id){
            $department=BuildingUser::where('id',$department_id)->with('role')->first();
        }
        
        $departmentNew = $user->department;
    
        $query = Issue::where('building_id', $department->building_id)
            // ->where('status', $request->status)
            ->with([
                'flat:id,name',
                'user',
                'block',
                'building:id,name',
                'photos',
                'department',
                'comments.user',
                'comments.replies.user'
            ]);
        if($department->role->slug != 'issue'){
            $query->where('role_id', $department->role_id)->where('role_user_id',$user->id)->where(function ($q) {
          $q->where('status', 'Completed')
            ->orWhere('status', 'Rejected');
      });
        }
        // Apply created_at date filter if both from_date and to_date are provided
        if ($request->filled('from_date') && $request->filled('to_date')) {
            try {
                $from = Carbon::parse($request->from_date)->startOfDay();
                $to = Carbon::parse($request->to_date)->endOfDay();
    
                $query->whereBetween('created_at', [$from, $to]);
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'error' => 'Invalid date format.'], 422);
            }
        }
    
        $issues = $query->get();
    
        return response()->json([
            'issues' => $issues
        ], 200);
    }
    




    public function get_comment_replies(Request $request)
    {
        $rules = [
            'comment_id' => 'required|exists:comments,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $comment = Comment::where('id',$request->comment_id)->with(['user','replies.user'])->first();
        return response()->json([
                'comment' => $comment,
        ],200);
    }
    
    public function get_building_gate_passes(Request $request)
    {
        $rules = [
            'status' => 'required|in:Approved,Rejected,Recheck,Completed,All',
            'fromdate' => 'nullable|date_format:d-m-Y',
            'todate' => 'nullable|date_format:d-m-Y',
            'count' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ], 422);
        }
    
        $user = Auth::User();
        $building = $user->gate->building;
    
        $query = GatePass::where('building_id', $building->id)
            ->with(['user', 'visitor', 'building', 'flat.block']);
    
        // Apply status filter
        if ($request->status !== 'All') {
            $query->where('status', $request->status);
        }
    
        // Apply date filters
        if ($request->filled('fromdate')) {
            $from = \Carbon\Carbon::createFromFormat('d-m-Y', $request->fromdate)->startOfDay();
            $query->whereDate('created_at', '>=', $from);
        }
    
        if ($request->filled('todate')) {
            $to = \Carbon\Carbon::createFromFormat('d-m-Y', $request->todate)->endOfDay();
            $query->whereDate('created_at', '<=', $to);
        }
    
        $count = $request->input('count');
        $page = $request->input('page', 1);
    
        if ($count) {
            $gate_passes = $query->orderBy('created_at', 'desc')->paginate($count, ['*'], 'page', $page);
    
            return response()->json([
                'gate_passes' => $gate_passes->items(),
                'total' => $gate_passes->total(),
                'current_page' => $gate_passes->currentPage(),
                'last_page' => $gate_passes->lastPage(),
            ], 200);
        } else {
            $gate_passes = $query->orderBy('created_at', 'desc')->get();
    
            return response()->json([
                'gate_passes' => $gate_passes,
                'total' => $gate_passes->count(),
                'current_page' => 1,
                'last_page' => 1,
            ], 200);
        }
    }
    

    
    public function resend_gate_pass_request(Request $request)
    {
        $rules = [
            'status' => 'required|in:Recheck',
            'gate_pass_id' => 'required|exists:gate_passes,id',
            'image' => 'nullable|image'
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        $gate_pass = GatePass::find($request->gate_pass_id);
        // if($request->hasFile('image')) {
        //     $file= $request->file('image');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($gate_pass->getImageFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/gate_pass/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $gate_pass->image = $filename;
        // }
        
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($gate_pass->image_filename)) {
                $file_path = public_path('images/' . $gate_pass->image_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'gate_pass/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/gate_pass/'), $filename);
            $gate_pass->image = $filename;
        }
        $gate_pass->status = $request->status;
        $gate_pass->save();
        return response()->json([
            'msg' => 'Recheck gate pass request send',
        ],200);
    }
    
    public function get_homepage_count(Request $request)
    {
        // visitors, gate-pass,parcels,vehicles
        $user = Auth::User();
        $building = $user->gate->building;
        
        
    //     // current_status
        $visitors_count = Visitor::where('building_id',$building->id)->where('status','Living')->count();
        $gate_pass_count = GatePass::where('building_id',$building->id)->whereNotIn('status', ['CheckedOut', 'CheckedOutExtraItem','ExtraItemGivenUser'])->count();
        $parcel_count = Parcel::where('building_id',$building->id)->where('status',['CheckIn', 'StoredInSecurity'])->count();
        
        
    //     // $vehicle_count = Vehicle::where('building_id',$building->id)->where('status','In')->whereIn('ownership',['Guest','Outsider'])->count();
        
        $vehicle_Own_count = Vehicle::where('building_id',$building->id)->whereIn('status', ['In', 'Out'])->where('ownership','Own')->count();
        $vehicle_outer_couter=Vehicle::where('building_id',$building->id)->where('status','In')->where('ownership','Outsider')->count();
        $vehicle_visitor_couter=Vehicle::where('building_id',$building->id)->where('status','In')->where('ownership','Guest')->count();
        
        
        $total_vehicle=0;
    
    $unreadCount = DatabaseNotification::where('user_id', $user->id)
        ->whereNull('read_at')
        ->count();
        
  
        return response()->json([
            'visitors_count' => $visitors_count,
            'gate_pass_count' => $gate_pass_count,
            'parcel_count' => $parcel_count,
            
            // 'building->id'=>$building->id,
            
            'vehicle_Own_count'=>$vehicle_Own_count,
            'vehicle_outer_couter'=>$vehicle_outer_couter,
            'vehicle_visitor_couter'=>$vehicle_visitor_couter,
            // 'total_vehicle'=>$total_vehicle,
            
        
            
            'unread_count' => $unreadCount,
             'iconBadge' => $unreadCount > 0 ? true : false,

        ],200);
    }
    
    public function extend_stay_time(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $visitor = Visitor::find($request->visitor_id);
        $flat = $visitor->flat;
    if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
        // send notification to user
        
                if($visitor->type == 'Planned'){
            $screentab = 'Planned Visitors';
            $categoryId = 'PlannedVisitors';
        }else{
            $screentab = 'Unplanned Visitors';
            $categoryId = 'UnplannedVisitors';
        }
                
$title = 'Extend Stay Time';
$body = "Its looks like {$visitor->head_name} visitor stay time is over, please increase stay time.";


        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'Visitors',
            'params' => json_encode([
                'ScreenTab' => $screentab,
            'visitor_id' => $visitor->id,
            'user_id'=>$user->id,
            'flat_id'=>$flat->id
            ]),
            'categoryId' => $categoryId,
            'channelId' => 'longring',
            'sound' => 'longring.wav',
            'type' => 'UNPLANNED_VISITOR_COMPLETED',
            'user_id' => (string)$user->id,
            'flat_id' => (string)$flat->id,
            'building_id' => (string)$flat->building_id,
            'actionButtons' => json_encode(["Allow", "Deny", "Stay at Lobby"]),
        ];
        
        
            // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $user->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ]
    );
        
        // $devices = DB::table('user_devices')
        //     ->where('user_id', $user->id)
        //     ->whereNotNull('fcm_token')
        //     ->where('is_active', 1)
        //     ->select('fcm_token', 'device_type')
        //     ->get();
    
        // // Setup Firebase for Android/Web
        // $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
        // $firebaseMessaging = $firebaseFactory->createMessaging();
    
        // $apnsClient = $this->apnsClient;
        // if($visitor->type == 'Planned'){
        //     $screentab = 'Planned Visitors';
        //     $categoryId = 'PlannedVisitors';
        // }else{
        //     $screentab = 'Unplanned Visitors';
        //     $categoryId = 'UnplannedVisitors';
        // }
        
        // $dataPayload = [
        //     'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //     'screen' => 'Visitors',
        //     'params' => json_encode(['ScreenTab' => $screentab,'visitor_id' => $visitor->id]),
        //     'categoryId' => $categoryId,
        //     'channelId' => 'security',
        //     'sound' => 'bellnotificationsound.wav',
        //     'type' => 'UNPLANNED_VISITOR_COMPLETED',
        //     'user_id' => (string)$user->id,
        //     'flat_id' => (string)$flat->id,
        //     'building_id' => (string)$flat->building_id,
        //     'actionButtons' => json_encode(["Allow", "Deny", "Stay at Lobby"]),
        // ];
        // $title = 'Extend Stay Time';
        // $body = 'Its looks like visitor stay time is over, please increase stay time.';
        // foreach ($devices as $device) {
        //     $token = $device->fcm_token;
        //     $type = strtolower($device->device_type); // ios, android, web
    
        //     if (in_array($type, ['android', 'web'])) {
        //         $message = CloudMessage::withTarget('token', $token)
        //             ->withNotification(Notification::create(
        //                 $title,
        //                 $body
        //             ))
        //             ->withData($dataPayload);
    
        //         try {
        //             $firebaseMessaging->send($message);
        //             // \Log::info("Firebase notification sent to: $token");
        //         } catch (\Exception $e) {
        //             \Log::error("FCM error for token $token: " . $e->getMessage());
        //         }
    
        //     } elseif ($type === 'ios') {
        //         $alert = Alert::create()
        //             ->setTitle($title)
        //             ->setBody($body);
        //         $payload = Payload::create()
        //             ->setAlert($alert)
        //             ->setSound('bellnotificationsound.wav')
        //             ->setCustomValue('click_action', $dataPayload['click_action'])
        //             ->setCustomValue('screen', $dataPayload['screen'])
        //             ->setCustomValue('params', $dataPayload['params'])
        //             ->setCustomValue('categoryId', $dataPayload['categoryId'])
        //             ->setCustomValue('channelId', $dataPayload['channelId'])
        //             ->setCustomValue('sound', $dataPayload['sound'])
        //             ->setCustomValue('type', $dataPayload['type'])
        //             ->setCustomValue('user_id', $dataPayload['user_id'])
        //             ->setCustomValue('flat_id', $dataPayload['flat_id'])
        //             ->setCustomValue('building_id', $dataPayload['building_id'])
        //             ->setCustomValue('actionButtons', $dataPayload['actionButtons']);
                    
        //         $notification = new ApnsNotification($payload, $token);

        //         try {
        //             $apnsClient->addNotification($notification);
        //             $responses = $apnsClient->push(); // returns an array of ApnsResponseInterface
        //             foreach ($responses as $response) {
        //                 if ($response->getStatusCode() === 200) {
        //                     // Push was successful
        //                 } else {
        //                     // Push failed, optionally log the error
        //                     \Log::error('APNs Error', [
        //                         'status' => $response->getStatusCode(),
        //                         'reason' => $response->getReasonPhrase(),
        //                         'error'  => $response->getErrorReason()
        //                     ]);
        //                 }
        //             }
        //         } catch (\Exception $e) {
        //             \Log::error("APNs exception: " . $e->getMessage());
        //         }
        //     }
        // }
        
        return response()->json([
                'visitor' => $visitor,
                'msg' => 'Visitor request sent successfully'
        ],200);
    }
    
    public function missing_alert(Request $request)
    {
        $rules = [
            'visitor_id' => 'required|exists:visitors,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $visitor = Visitor::find($request->visitor_id);
        $flat = $visitor->flat;
        if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
            $user = $flat->owner;
        }else{
            $user = $flat->tanent;
        }
        
        if($visitor->type == 'Planned'){
            $screentab = 'Planned Visitors';
            $categoryId = 'PlannedVisitors';
        }else{
            $screentab = 'Unplanned Visitors';
            $categoryId = 'UnplannedVisitors';
        }
        
$title = 'Missing alert';
$body = "It looks like {$visitor->head_name} visitor is missing.";

        
                $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'Visitors',
            'params' => json_encode([
                'ScreenTab' => $screentab,
            'visitor_id' => $visitor->id,
            'user_id'=>$user->id,
            'flat_id'=>$flat->id
            ]),
            'categoryId' => $categoryId,
            'channelId' => 'longring',
            'sound' => 'longring.wav',
            'type' => 'UNPLANNED_VISITOR_COMPLETED',
            'user_id' => (string)$user->id,
            'flat_id' => (string)$flat->id,
            'building_id' => (string)$flat->building_id,
            'actionButtons' => json_encode(["Allow", "Deny", "Stay at Lobby"]),
        ];
        
        
            // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $user->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ]
    );
    
    
        // // send notification to user
        // $devices = DB::table('user_devices')
        //     ->where('user_id', $user->id)
        //     ->whereNotNull('fcm_token')
        //     ->where('is_active', 1)
        //     ->select('fcm_token', 'device_type')
        //     ->get();
    
        // // Setup Firebase for Android/Web
        // $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
        // $firebaseMessaging = $firebaseFactory->createMessaging();
    
        // $apnsClient = $this->apnsClient;
        // if($visitor->type == 'Planned'){
        //     $screentab = 'Planned Visitors';
        //     $categoryId = 'PlannedVisitors';
        // }else{
        //     $screentab = 'Unplanned Visitors';
        //     $categoryId = 'UnplannedVisitors';
        // }
        
        // $dataPayload = [
        //     'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        //     'screen' => 'Visitors',
        //     'params' => json_encode(['ScreenTab' => $screentab,'visitor_id' => $visitor->id]),
        //     'categoryId' => $categoryId,
        //     'channelId' => 'security',
        //     'sound' => 'bellnotificationsound.wav',
        //     'type' => 'UNPLANNED_VISITOR_COMPLETED',
        //     'user_id' => (string)$user->id,
        //     'flat_id' => (string)$flat->id,
        //     'building_id' => (string)$flat->building_id,
        //     'actionButtons' => json_encode(["Allow", "Deny", "Stay at Lobby"]),
        // ];
        // $title = 'Missing alert';
        // $body = 'Its looks like visitor is missing.';
        // foreach ($devices as $device) {
        //     $token = $device->fcm_token;
        //     $type = strtolower($device->device_type); // ios, android, web
    
        //     if (in_array($type, ['android', 'web'])) {
        //         $message = CloudMessage::withTarget('token', $token)
        //             ->withNotification(Notification::create(
        //                 $title,
        //                 $body
        //             ))
        //             ->withData($dataPayload);
    
        //         try {
        //             $firebaseMessaging->send($message);
        //             // \Log::info("Firebase notification sent to: $token");
        //         } catch (\Exception $e) {
        //             \Log::error("FCM error for token $token: " . $e->getMessage());
        //         }
    
        //     } elseif ($type === 'ios') {
        //         $alert = Alert::create()
        //             ->setTitle($title)
        //             ->setBody($body);
        //         $payload = Payload::create()
        //             ->setAlert($alert)
        //             ->setSound('bellnotificationsound.wav')
        //             ->setCustomValue('click_action', $dataPayload['click_action'])
        //             ->setCustomValue('screen', $dataPayload['screen'])
        //             ->setCustomValue('params', $dataPayload['params'])
        //             ->setCustomValue('categoryId', $dataPayload['categoryId'])
        //             ->setCustomValue('channelId', $dataPayload['channelId'])
        //             ->setCustomValue('sound', $dataPayload['sound'])
        //             ->setCustomValue('type', $dataPayload['type'])
        //             ->setCustomValue('user_id', $dataPayload['user_id'])
        //             ->setCustomValue('flat_id', $dataPayload['flat_id'])
        //             ->setCustomValue('building_id', $dataPayload['building_id'])
        //             ->setCustomValue('actionButtons', $dataPayload['actionButtons']);
                    
        //         $notification = new ApnsNotification($payload, $token);

        //         try {
        //             $apnsClient->addNotification($notification);
        //             $responses = $apnsClient->push(); // returns an array of ApnsResponseInterface
        //             foreach ($responses as $response) {
        //                 if ($response->getStatusCode() === 200) {
        //                     // Push was successful
        //                 } else {
        //                     // Push failed, optionally log the error
        //                     \Log::error('APNs Error', [
        //                         'status' => $response->getStatusCode(),
        //                         'reason' => $response->getReasonPhrase(),
        //                         'error'  => $response->getErrorReason()
        //                     ]);
        //                 }
        //             }
        //         } catch (\Exception $e) {
        //             \Log::error("APNs exception: " . $e->getMessage());
        //         }
        //     }
        // }
        
        return response()->json([
                'visitor' => $visitor,
                'msg' => 'Visitor request sent successfully',
                '$user->id'=>$user->id
        ],200);
    }

    
    public function send_push_notification(Request $request){
    $rules = [
        'device_token' => 'required|string',
        'device_type' => 'required|in:ios,android,web',
        'title' => 'required|string',
        'body' => 'required|string',
        'screen' => 'required|string',
        'params' => 'nullable|array',
        'categoryId' => 'nullable|string',
        'channelId' => 'nullable|string',
        'sound' => 'nullable|string',
        'type' => 'nullable|string',
        'user_id' => 'nullable|string',
        'flat_id' => 'nullable|string',
        'building_id' => 'nullable|string',
    ];

    $validation = \Validator::make($request->all(), $rules);

    if ($validation->fails()) {
        return response()->json([
            'status' => 'error',
            'error' => $validation->errors()->first()
        ], 422);
    }

    $token = $request->device_token;
    $type = strtolower($request->device_type);

    // Common payload data
    $dataPayload = [
        // 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        'click_action' => 'expo-notifications',
        'screen' => $request->screen,
        'params' => json_encode($request->params ?? []),
        'categoryId' => $request->categoryId ?? '',
        'channelId' => $request->channelId ?? '',
        'sound' => $request->sound ?? 'default',
        'android_channel_id'=>'longring',
        'type' => $request->type ?? '',
        'user_id' => $request->user_id ?? '',
        'flat_id' => $request->flat_id ?? '',
        'building_id' => $request->building_id ?? '',
    ];

    if (in_array($type, ['android', 'web'])) {
        // Firebase push notification
        $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
        $messaging = $firebaseFactory->createMessaging();

        $notification = Notification::create($request->title, $request->body);

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData($dataPayload);

        try {
            $messaging->send($message);
            return response()->json(['status' => 'success', 'msg' => 'Notification sent via Firebase']);
        } catch (\Exception $e) {
            \Log::error("Firebase notification error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'error' => 'Failed to send Firebase notification', 'hero'=>$e->getMessage()], 500);
        }
    } elseif ($type === 'ios') {
        // APNs push notification

        $alert = Alert::create()
            ->setTitle($request->title)
            ->setBody($request->body);

        $payload = Payload::create()
            ->setAlert($alert)
            ->setSound($request->sound ?? 'default');

        // Add custom data fields
        foreach ($dataPayload as $key => $value) {
            $payload->setCustomValue($key, $value);
        }

        $notification = new ApnsNotification($payload, $token);

        // Initialize APNs client, assuming you have $this->apnsClient available,
        // otherwise instantiate here with your APNs cert/key info:
        // $apnsClient = new ApnsClient([...]);

        try {
            $this->apnsClient->addNotification($notification);
            $responses = $this->apnsClient->push();

            foreach ($responses as $response) {
                if ($response->getStatusCode() !== 200) {
                    \Log::error('APNs Error', [
                        'status' => $response->getStatusCode(),
                        'reason' => $response->getReasonPhrase(),
                        'error'  => $response->getErrorReason()
                    ]);
                    return response()->json(['status' => 'error', 'error' => 'APNs notification failed'], 500);
                }
            }

            return response()->json(['status' => 'success', 'msg' => 'Notification sent via APNs']);
        } catch (\Exception $e) {
            \Log::error("APNs exception: " . $e->getMessage());
            return response()->json(['status' => 'error', 'error' => 'Failed to send APNs notification'], 500);
        }
    }

    return response()->json(['status' => 'error', 'error' => 'Unsupported device type'], 400);
}
    
    public function building_policy()
    {
        $user = Auth::User();
        $flat=AuthHelper::flat();
        $building_policy = Building::where('id',$flat->building_id)->pluck('building_policy')->first();
        return response()->json([
                'building_policy' => $building_policy,
        ],200);
    }
}