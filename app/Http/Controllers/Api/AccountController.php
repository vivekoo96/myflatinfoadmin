<?php

namespace App\Http\Controllers\Api;
use App\Jobs\SendForgetPasswordEmail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\User;
use App\Models\Building;
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
use App\Models\Expense;
use App\Models\Maintenance;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
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

use Illuminate\Support\Arr;


use \Hash;
use \Auth;

// Update Thu 20 Nov 01:50


class AccountController extends Controller
{
    public function __construct()
    {
        
        $rdata = Setting::findOrFail(1);
        $this->keyId = $rdata->razorpay_key;
        $this->keySecret = $rdata->razorpay_secret;
        $this->displayCurrency = 'INR';

        $this->api = new Api($this->keyId, $this->keySecret);
    }
    
    public function get_setting()
    {
        $setting = Setting::first();
        return response()->json([
            'setting' => $setting
        ],200); 
    }
    
    public function user_status()
    {
        $user = Auth::User();
        
        return response()->json([
            'profile_status' => $user->profile_status,
            'status' => $user->status,
        ],200); 
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
                    'msg' => 'OTP has been sent successfully.'
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

        // $validation = \Validator::make( $request->all(), $rules );
        // $error = $validation->errors()->first();
        // if($error){
        //     return response()->json([
        //         'error' => $error
        //     ],422);
        // }
        
        $user = User::where('email',$request->email)->first();
        if(!$user){
            return response()->json([
                'error' => 'This email is not registered with us'
            ],422);
        }
        if($user->hasRole('accounts')){
            //
        }else{
            return response()->json([
                'error' => 'This user is not belongs to accounts'
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
            'email' => 'required|email|exists:users,email',
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
        if(!$user){
            return response()->json([
                'error' => 'This user is not belongs to accounts'
            ],422);
        }
        if($user->hasRole('accounts')){
            //
        }else{
            return response()->json([
                'error' => 'This user is not belongs to accounts'
            ],422);
        }
        if($user){
            if (Hash::check($request->otp, $user->otp)) {
                if($user->otp_status == 'Sent'){
                    $user->otp_status = 'Verified';
                    $token = $user->createToken('MyApp')->accessToken;
                    $user->api_token = $token;
                    $user->device_token = $request->device_token;
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
        
        if($request->login_type == "accounts"){
        // Get building user records for this user
    $building_users = BuildingUser::where('user_id', $user->id)
        ->get(['id', 'role_id', 'user_id']);

    // Extract role IDs
    $role_ids = $building_users->pluck('role_id');

    // Fetch departments for these role IDs where type is 'issue'
    $departments = Role::whereIn('id', $role_ids)
        ->where('type', 'default')
        ->where('slug', 'accounts')
        ->get();

    // If no departments found, return error
    if ($departments->isEmpty()) {
        return response()->json([
            'error' => 'This account is not registered with any accounts.',
        ], 422);
    }
}


        if($user && $user->status == 'Active'){
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('MyApp')->accessToken;
                $user->api_token = $token;
                $user->device_token = $request->device_token;
                $user->fcm_token = $request->fcm_token;
                $user->platform = $request->platform;
                $user->save();
                Auth::login($user, true);
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
        return response()->json([
            'error' => 'This email is not registered with us'
        ],422);
    }

    public function profile(Request $request)
    {
        $user = Auth::User();
        $building = $user->building;
        if ($user->fcm_token) {
            $factory = (new Factory)
                ->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json')); // adjust path if needed
    
            $messaging = $factory->createMessaging();
    
            $notification = Notification::create('Welcome, ' . $user->name, 'You accessed your profile!');
    
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withData([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'profile_access',
                    'user_id' => (string)$user->id,
                ]);
    
            try {
                $messaging->send($message);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                return response()->json(['error' => 'FCM Error: ' . $e->getMessage()], 500);
            }
        }
        
        // send push notification
        return response()->json([
            'user' => $user,
        ],200);
    }
    
    public function update_profile(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'first_name' => 'required|string|max:255|regex:/^[A-Z][a-z]*$/',
            'last_name' => 'required|string|max:255|regex:/^[A-Z][a-z]*$/',
            // 'phone' => 'required|unique:users,phone,' . $user->id . '|regex:/^([0-9\s\-\+\(\)]*)$/|size:10',
            
            'gender' => 'required|in:Male,Female,Other',
            'city_id' => 'required|exists:cities,id|numeric',
            'address' => 'required|string|min:4',
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
        $user->city_id = $request->city_id;
        $user->address = $request->address;
        // $user->pincode = $request->pincode;
        $user->save();
    
        return response()->json([
            'msg' => 'Profile updated successfully',
            'user' => $user
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
    
    public function get_buildings(Request $request)
    {
        
    $user = Auth::User();
                
    // Get Accounts role ID
    $accountsRoleId = Role::where('name', 'Accounts')->value('id');

    // Step 1: Get building_ids where role = Accounts
    $buildingIds = BuildingUser::where('user_id', $user->id)
        ->where('role_id', $accountsRoleId)
        ->pluck('building_id');
        
    $buildings = Building::whereIn('id', $buildingIds)->get();

        return response()->json([
            'buildings' => $buildings
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
        $building = $user->allBuildings()->where('id',$request->building_id);
        if(!$building){
            return response()->json([
                'error' => 'This building is not belongs to this account',
            ], 422);
        }
        $user->building_id = $request->building_id;
        $user->device_token = $request->device_token;
        $user->fcm_token = $request->fcm_token;
        $user->platform = $request->platform;
        $user->save();
        

        return response()->json([
            'building' => $building,
        ], 200);
    }

    public function get_flats(Request $request)
    {
        $user = Auth::User();
        $building = Building::where('id',$user->building_id)->with(['blocks.flats'])->get();
        return response()->json([
                 'message' => 'Building data retrieved successfully',
            'building' => $building
        ], 200);
    }

    public function flat_detailsxx(Request $request)
    {
        $user = Auth::User();
        $flat = Flat::where('building_id',$user->building_id)->where('id',$request->flat_id)->with(['building','block','family_members','parcels','maintenance_payments','essential_payments'])->first();
        
          // Fetch transactions for this flat where model = 'Corpus'
    $transactions = Transaction::where('flat_id', $request->flat_id)
        ->where('model', 'Corpus')
        ->get();
        return response()->json([
            'flat' => $flat,
            'transactions'=>$transactions
        ], 200);
    }
    
    
    public function flat_details(Request $request)
{
    $user = Auth::user();

    $flat = Flat::where('building_id', $user->building_id)
        ->where('id', $request->flat_id)
        ->with([
            'building',
            'block',
            'family_members',
            'parcels',
            'maintenance_payments',
            'essential_payments'
        ])
        ->first();

    if (!$flat) {
        return response()->json(['message' => 'Flat not found'], 404);
    }

    // Fetch transactions for this flat where model = 'Corpus'
    $transactions = Transaction::where('flat_id', $request->flat_id)
        ->where('model', 'Corpus')
        ->get();

    /* ================= Treasurer Logic ================= */

    $building = $flat->building;

    $treasurerId = $building->treasurer_id ?? $building->user_id;

    $treasurer = User::find($treasurerId);

    $treasurerName = null;
    $treasurerPhone = null;

    if ($treasurer) {
        $treasurerName = $treasurer->first_name . ' ' . $treasurer->last_name;
        $treasurerPhone = $treasurer->phone;
    }

    /* =================================================== */

    return response()->json([
        'flat' => $flat,
        'transactions' => $transactions,
        'treasurers_name' => $treasurerName,
        'treasurers_phone' => $treasurerPhone,
    ], 200);
}

    
   public function update_corpus_fund(Request $request)
{
    $rules = [
        'flat_id' => 'required|exists:flats,id',
        'corpus_fund' => 'required',
        'is_corpus_paid' => 'required',
        'corpus_paid_on' => 'nullable|date',
        'payment_type' => 'required',
    ];

    $msg = 'Corpus fund updated successfully';
    $flat = Flat::withTrashed()->find($request->flat_id);

    $validation = \Validator::make($request->all(), $rules);

    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first()
        ], 422);
    }

    // Update flat corpus fields
    $flat->corpus_fund = $request->corpus_fund;
    $flat->is_corpus_paid = $request->is_corpus_paid;
    $flat->corpus_paid_on = $request->corpus_paid_on;
    $flat->corpus_payment_type = $request->payment_type;

    if (empty($flat->bill_no)) {
        $flat->bill_no = Str::random(16);
    }

    if ($request->is_corpus_paid == 'No') {
        $flat->corpus_paid_on = null;
        $flat->bill_no = null;
    }

    $flat->save();

    // Transaction update/create
    $transaction = Transaction::where('model', 'Corpus')
        ->where('model_id', $flat->id)
        ->first();

    if (!$transaction) {
        $transaction = new Transaction();
    }

    $transaction->building_id = $flat->building_id;
    $transaction->user_id = $flat->owner_id;
    $transaction->model = 'Corpus';
    $transaction->type = 'Credit';
    $transaction->flat_id = $flat->id;
    $transaction->payerrole_id = Auth::id();
    $transaction->payment_type = $request->payment_type;
    $transaction->date = $request->corpus_paid_on;
    $transaction->amount = $request->corpus_fund;
    $transaction->reciept_no = 'RCP' . rand(10000000, 99999999);
    $transaction->desc = 'Corpus Fund for ' . $flat->name . ' through Accountant : '.Auth::User()->name;
    $transaction->status = 'Success';
    $transaction->save();

    /**
     * ðŸ”” Send Notification to Flat Owner
     */
    if ($flat->owner_id) {
        $title = 'Corpus Fund Updated';
        $body = 'Your corpus fund details for flat ' . $flat->name . ' have been updated. through Accountant : '.Auth::User()->name;

        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'CorpusFundPage',
            'params' => json_encode([
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
            ]),
            'categoryId' => 'CorpusFundUpdate',
            'channelId' => 'default',
            'sound' => 'default',
            'type' => 'CORPUS_FUND_UPDATE',
            'user_id' => (string) $flat->owner_id,
            'flat_id' => (string) $flat->id,
            'building_id' => (string) $flat->building_id,
        ];

        // Assuming NotificationHelper is your custom FCM helper
        NotificationHelper::sendNotification(
            $flat->owner_id,
            $title,
            $body,
            $dataPayload,
            [
                'from_id' => Auth::id(),
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'corpus_fund_update',
                'ios_sound' => 'default'
            ]
        );
    }

    return response()->json([
        'msg' => $msg,
        // 'djh'=>Auth::User()->name
    ], 200);
}

    
    public function get_opening_balance(Request $request)
    {
        $user = Auth::User();
        $building = $user->building()->select(['id','maintenance_in_bank','maintenance_in_hand','corpus_in_bank','corpus_in_hand'])->first();
        
        $can_update = 'Yes';
        $transaction = Transaction::where('building_id',$building->id)->where('model','Opening')->first();
        if($transaction){
            $can_update = 'No';
        }
        return response()->json([
            'building' => $building,
            'can_update' => $can_update
        ], 200);
    }
    
    public function update_opening_balance(Request $request)
    {
        $rules = [
            'maintenance_in_bank' => 'required|int',
            'maintenance_in_hand' => 'required|int',
            'corpus_in_bank' => 'required|int',
            'corpus_in_hand' => 'required|int',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
        $error = $validation->errors()->first();
        if ($error) {
            return response()->json([
                'error' => $error
            ], 422);
        }
        
        $user = Auth::User();
        $building = $user->building;
        $transaction = Transaction::where('building_id',$building->id)->where('model','Opening')->first();
        if($transaction){
            return response()->json([
                'error' => 'Opening balance already updated'
            ], 422);
        }
        $building->maintenance_in_bank = $request->maintenance_in_bank;
        $building->maintenance_in_hand = $request->maintenance_in_hand;
        $building->corpus_in_bank = $request->corpus_in_bank;
        $building->corpus_in_hand = $request->corpus_in_hand;
        $building->save();
        
        $transaction = new Transaction();
        $transaction->building_id = $building->id;
        $transaction->user_id = $building->user_id;
        // $transaction->model = 'Opening';
        $transaction->model = 'Maintenance';
        $transaction->type = 'Credit';
        $transaction->payment_type = 'InBank';
        $transaction->amount = $request->maintenance_in_bank;
        $transaction->desc = 'Opening Maintenance Balance InBank';
        $transaction->reciept_no = 'RCP'.rand(10000000,99999999);
        $transaction->status = 'Success';
        $transaction->date = now()->toDateString();
        $transaction->save();
        
        $transaction = new Transaction();
        $transaction->building_id = $building->id;
        $transaction->user_id = $building->user_id;
        // $transaction->model = 'Opening';
        $transaction->model = 'Maintenance';
        $transaction->type = 'Credit';
        $transaction->payment_type = 'InHand';
        $transaction->amount = $request->maintenance_in_hand;
        $transaction->desc = 'Opening Maintenance Balance InHand';
        $transaction->reciept_no = 'RCP'.rand(10000000,99999999);
        $transaction->status = 'Success';
        $transaction->date = now()->toDateString();
        $transaction->save();
        
        $transaction = new Transaction();
        $transaction->building_id = $building->id;
        $transaction->user_id = $building->user_id;
        // $transaction->model = 'Opening';
         $transaction->model = 'Corpus';
        $transaction->type = 'Credit';
        $transaction->payment_type = 'InBank';
        $transaction->amount = $request->corpus_in_bank;
        $transaction->desc = 'Opening Corpus Balance InBank';
        $transaction->reciept_no = 'RCP'.rand(10000000,99999999);
        $transaction->status = 'Success';
        $transaction->date = now()->toDateString();
        $transaction->save();
        
        $transaction = new Transaction();
        $transaction->building_id = $building->id;
        $transaction->user_id = $building->user_id;
        // $transaction->model = 'Opening';
         $transaction->model = 'Corpus';
        $transaction->type = 'Credit';
        $transaction->payment_type = 'InHand';
        $transaction->amount = $request->corpus_in_hand;
        $transaction->desc = 'Opening Corpus Balance InHand';
        $transaction->reciept_no = 'RCP'.rand(10000000,99999999);
        $transaction->status = 'Success';
        $transaction->date = now()->toDateString();
        $transaction->save();
        
        return response()->json([
            'msg' => 'Opening blance updated successfully'
        ], 200);
    }
    
    public function get_model_data(Request $request)
    {
        $user = Auth::User();
        $model = $request->model;
        if ($model == 'Event') {
            $data = Event::select(['id', 'name'])->where('building_id',$user->building_id)->get();
        } elseif ($model == 'Essential') {
            $data = Essential::select(['id', DB::raw('reason as name')])->where('building_id',$user->building_id)->get();
        } elseif ($model == 'Facility') {
            $data = Facility::select(['id', 'name'])->where('building_id',$user->building_id)->get();
        }
        else {
            $data = collect(); // Empty collection if no model matched
        }
        return response()->json([
                'data' => $data
        ],200);
    }
    
    public function income_and_expenditure(Request $request)
    {
        $building = Auth::User()->building;
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
        $inhand = $inhand + $building->maintenance_in_hand + $building->corpus_in_hand;
        $inbank = $inbank + $building->maintenance_in_bank + $building->corpus_in_bank;
        $building = Auth::User()->building;
        return response()->json([
            'building' => $building,
            'transactions' => $transactions,
            'inhand' => $inhand,
            'inbank' => $inbank,
            'total_debit' => $total_debit,
            'total_credit' => $total_credit,
        ], 200);
    }
    
    // public function form_payments()
    // {
    //     $user = Auth::User();
    //     $building = $user->building;
    //     $expenses = $building->expenses()->where('type','Debit')->orderBy('date','desc')->get();
        

    //     return response()->json([
    //         'expenses' => $expenses
    //     ], 200);
    // }
    public function form_paymentsx2()
{
    $user = Auth::user();
    $building = $user->building;

    // Fetch all debit expenses for the building, including transaction info
    $expenses = $building->expenses()
        ->where('type', 'Debit')
        ->orderBy('date', 'desc')
        ->with('transaction:id,reciept_no') // eager load only needed fields
        ->get()
        ->map(function ($expense) {
            // Attach reciept_no directly in the response
            $expense->reciept_no = $expense->transaction->reciept_no ?? null;
            unset($expense->transaction); // optional: remove nested transaction object
            return $expense;
        });

    return response()->json([
        'expenses' => $expenses,
    ], 200);
}


public function form_payments()
{
    $user = Auth::user();
    $building = $user->building;

    $expenses = $building->expenses()
        ->where('type', 'Debit')
   ->orderByDesc('created_at')
->orderByDesc('date')
        ->with('transaction:id,reciept_no')
        ->get()
        ->map(function ($expense) {

            // receipt no
            $expense->reciept_no = $expense->transaction->reciept_no ?? null;
            unset($expense->transaction);

            // model_name logic
            $expense->model_name = match ($expense->model) {
                'Event' => Event::where('id', $expense->model_id)->value('name'),
                'Facility' => Facility::where('id', $expense->model_id)->value('name'),
                'Essential' => Essential::where('id', $expense->model_id)->value('reason'),
                default => null,
            };

            return $expense;
        });

    return response()->json([
        'expenses' => $expenses,
    ], 200);
}
    
public function form_recieptsxx()
{
    $user = Auth::user();
    $building = $user->building;

    // Get all credit-type expenses for the user's building with their transaction receipt number
    $expenses = $building->expenses()
        ->where('type', 'Credit')
        ->orderBy('date', 'desc')
        ->with('transaction:id,reciept_no') // eager load only the needed field
        ->get()
        ->map(function ($expense) {
            // Flatten reciept_no to the expense level
            $expense->reciept_no = $expense->transaction->reciept_no ?? null;
            unset($expense->transaction); // optional: remove nested relationship
            return $expense;
        });

    return response()->json([
        'expenses' => $expenses
    ], 200);
}

public function form_reciepts()
{
    $user = Auth::user();
    $building = $user->building;

    $expenses = $building->expenses()
        ->where('type', 'Credit')
        ->orderByDesc('created_at')
        ->orderByDesc('date')
        ->with('transaction:id,reciept_no')
        ->get()
        ->map(function ($expense) {

            // receipt no
            $expense->reciept_no = $expense->transaction->reciept_no ?? null;
            unset($expense->transaction);

            // model_name logic
            $expense->model_name = match ($expense->model) {
                'Event'     => Event::where('id', $expense->model_id)->value('name'),
                'Facility'  => Facility::where('id', $expense->model_id)->value('name'),
                'Essential' => Essential::where('id', $expense->model_id)->value('reason'),
                default     => null,
            };

            return $expense;
        });

    return response()->json([
        'expenses' => $expenses
    ], 200);
}


    
    public function form_store_payment(Request $request)
    {
        $rules = [
            'model' => 'required',
            'model_id' => 'nullable|int',
            'payment_type' => 'required|in:InHand,InBank',
            'reason' => 'required',
            'amount' => 'required',
            'date' => 'required',
            'image' => 'nullable|image'
        ];
    
        $msg = 'Expense added Susccessfully';
        $expense = new Expense();
    
        if ($request->id) {
            $expense = Expense::find($request->id);
            $msg = 'Expense updated Susccessfully';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            
            return response()->json([
                'error' => $validation->errors()->first()
            ],422);
        }
        $user = Auth::User();
        $expense->user_id = $user->id;
        $expense->building_id = $user->building_id;
        $expense->model = $request->model;
        $expense->model_id = $request->model_id;
        $expense->payment_type = $request->payment_type;
        $expense->reason = $request->reason;
        $expense->type = 'Debit';
        $expense->date = $request->date;
        $expense->amount = $request->amount;
        // if($request->hasFile('image')) {
        //     $file= $request->file('image');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($expense->getImageFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/expenses/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $expense->image = $filename;
        // }
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($expense->photo_filename)) {
                $file_path = public_path('images/' . $expense->image_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'expenses/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/expenses/'), $filename);
            $expense->image = $filename;
        }
        
        $expense->save();
        $transaction = new Transaction();
        $transaction->building_id = $user->building_id;
        $transaction->user_id = $user->id;
        $transaction->model = $request->model;
        $transaction->model_id = $request->model_id;
        $transaction->date = $request->date;
        $transaction->type = 'Debit';
        $transaction->payment_type = $request->payment_type;
        $transaction->amount = $request->amount;
        $transaction->reciept_no = 'EXPS'.rand(10000000,99999999);
        $transaction->desc = $request->reason;
        $transaction->status = 'Success';
        $transaction->save();
        
        $expense->transaction_id = $transaction->id;
        $expense->save();
        
        return response()->json([
            'msg' => $msg
        ], 200);
    }
    
    public function form_store_reciept(Request $request)
    {
        $rules = [
            'name'=>'required',
            'model' => 'required',
            'model_id' => 'nullable|int',
            'payment_type' => 'required|in:InHand,InBank',
            'reason' => 'required',
            'amount' => 'required',
            'date' => 'required',
            'image' => 'nullable|image'
        ];
    
        $msg = 'Reciept added Susccessfully';
        $expense = new Expense();
    
        if ($request->id) {
            $expense = Expense::find($request->id);
            $msg = 'Reciept updated Susccessfully';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            
            return response()->json([
                'error' => $validation->errors()->first()
            ],422);
        }
        $user = Auth::User();
        $expense->ename =$request->name;
        $expense->user_id = $user->id;
        $expense->building_id = $user->building_id;
        $expense->model = $request->model;
        $expense->model_id = $request->model_id;
        $expense->payment_type = $request->payment_type;
        $expense->reason = $request->reason;
        $expense->type = 'Credit';
        $expense->date = $request->date;
        $expense->amount = $request->amount;
        // if($request->hasFile('image')) {
        //     $file= $request->file('image');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($expense->getImageFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/expenses/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $expense->image = $filename;
        // }
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($expense->photo_filename)) {
                $file_path = public_path('images/' . $expense->image_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'expenses/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/expenses/'), $filename);
            $expense->image = $filename;
        }
        $expense->save();
        $transaction = new Transaction();
        $transaction->building_id = $user->building_id;
        $transaction->user_id = $user->id;
        $transaction->model = $request->model;
        $transaction->model_id = $request->model_id;
        $transaction->date = $request->date;
        $transaction->type = 'Credit';
        $transaction->payment_type = $request->payment_type;
        $transaction->amount = $request->amount;
        $transaction->reciept_no = 'RCPT'.rand(10000000,99999999);
        $transaction->desc = $request->reason;
        $transaction->status = 'Success';
        $transaction->save();
        
        $expense->transaction_id = $transaction->id;
        $expense->save();
        
        return response()->json([
            'msg' => $msg
        ], 200);
    }
    
    
     public function download_expense_receipt(Request $request)
{
    $request->validate([
        'expense_id' => 'required|exists:expenses,id',
    ]);

    $user = Auth::user();
    $building = $user->building;
     $setting = Setting::first();
    $expense = Expense::with('transaction')
        ->where('id', $request->expense_id)
        ->where('building_id', $building->id)
        ->firstOrFail();

    $data = [
        'logo' => $setting->logo,
        'building_name' => $building->name,
        'receipt_no' => optional($expense->transaction)->reciept_no ?? 'N/A',
        'model' => $expense->model,
        'model_name' => $expense->model_name ?? 'N/A',
        'amount' => number_format($expense->amount, 2),
        'payment_type' => $expense->payment_type === 'InHand' ? 'In Cash' : 'In Bank',
        'date' => $expense->date,
        'reason' => $expense->reason,
        'issued_by' => trim($user->first_name . ' ' . $user->last_name),
        'issued_on' => now()->format('d-m-Y'),
    ];

    try {
        $pdf = Pdf::loadView('pdf.expense_receipt', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'attachment; filename="receipt_'.$expense->id.'.pdf"'
            );

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'PDF generation failed',
            'message' => $e->getMessage()
        ], 500);
    }
}
    
    //   public function download_expense_receipt(Request $request)
    // {
    //     $rules = [
    //         'expense_id' => 'required|exists:expenses,id',
    //     ];

    //     $validation = \Validator::make($request->all(), $rules);

    //     if ($validation->fails()) {
    //         return response()->json([
    //             'error' => $validation->errors()->first()
    //         ], 422);
    //     }

    //     $user = Auth::User();
    //     $building = $user->building;

    //     // Fetch expense with transaction
    //     $expense = Expense::where('id', $request->expense_id)
    //         ->where('building_id', $building->id)
    //         ->with('transaction')
    //         ->first();

    //     if (!$expense) {
    //         return response()->json([
    //             'error' => 'Expense not found'
    //         ], 422);
    //     }

    //     // Prepare receipt data
    //     $data = [
    //         'logo' => $building->image ?? asset('public/pdfImage/Transparent.png'),
    //         'building_name' => $building->name,
    //         'receipt_no' => $expense->transaction ? $expense->transaction->reciept_no : 'N/A',
    //         'model' => $expense->model,
    //         'model_name' => $expense->model_name ?? 'N/A',
    //         'amount' => number_format($expense->amount, 2),
    //         'payment_type' => $expense->payment_type == 'InHand' ? 'In Cash' : 'In Bank',
    //         'date' => $expense->date,
    //         'reason' => $expense->reason,
    //         'issued_by' => $user->first_name . ' ' . $user->last_name,
    //         'issued_on' => now()->toDateString(),
    //     ];

    //     // Generate PDF
    //     $pdf = Pdf::loadView('pdf.expense_receipt', $data);
        
    //     $filename = 'receipt_' . str_replace(' ', '_', $expense->model) . '_' . $expense->transaction->reciept_no . '.pdf';

    //     return $pdf->download($filename);
    // }
    
    public function generate_maintenance(Request $request)
    {
        $building = Auth::User()->building;
        $maintenances = $building->maintenances;
        return response()->json([
            'maintenances' => $maintenances,
        ], 200);
    }
    
      public function add_new_maintenance(Request $request)
    {
        // Permission check
   

        $rules = [
            'maintenance_id' => 'nullable|exists:maintenances,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'amount' => 'required|numeric|min:0',
            'vacant_amount' => 'required|numeric|min:0',
            'maintenance_type' => 'required|in:Areawise,Custom',
            'due_date' => 'required|date|after_or_equal:from_date',
            'late_fine_type' => 'required|in:Percentage,Daily,Fixed',
            'late_fine_value' => 'required|numeric|min:0',
            'gst' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:Active,Pending',
        ];

        // Custom validation messages
        $messages = [
            'to_date.after_or_equal' => 'To date must be after or equal to from date.',
            'due_date.after_or_equal' => 'Due date must be after or equal to from date.',
            'amount.min' => 'Amount must be greater than or equal to 0.',
            'vacant_amount.min' => 'Vacant amount must be greater than or equal to 0.',
            'late_fine_value.min' => 'Late fine value must be greater than or equal to 0.',
            'gst.max' => 'GST cannot be more than 100%.',
        ];
    
        $msg = 'Maintenance added Successfully';
        $maintenance = new Maintenance();
        $oldStatus = null;

        if ($request->maintenance_id) {
            $maintenance = Maintenance::withTrashed()->find($request->maintenance_id);
            $msg = 'Maintenance updated Successfully';
            $oldStatus = $maintenance->status; // Capture previous status for status change detection
        }
    
        $validation = \Validator::make($request->all(), $rules, $messages);
    
        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->first()], 422);
        }

        $user = Auth::User();
        $maintenance->user_id = $user->id;
        $maintenance->building_id = $user->building_id;
        $maintenance->maintenance_created_by = Auth::User()->id;
        $maintenance->from_date = $request->from_date;
        $maintenance->to_date = $request->to_date;
        $maintenance->amount = $request->amount;
        $maintenance->vacant_amount = $request->vacant_amount;
        $maintenance->maintenance_type = $request->maintenance_type;
        $maintenance->due_date = $request->due_date;
        $maintenance->late_fine_type = $request->late_fine_type;
        $maintenance->late_fine_value = $request->late_fine_value;
        $maintenance->gst = $request->gst;
        $maintenance->status = $request->status;
        $maintenance->save();
        
        // Only create/update maintenance payments if status is Active (not Pending)
        if ($request->status === 'Active') {
            foreach ($user->building->flats as $flat) {
                if ($flat->sold_out == 'Yes') {
                    $maintenance_payment = null;
                    
                    if ($request->maintenance_id) {
                        // When editing, check if payment already exists
                        $maintenance_payment = MaintenancePayment::where('flat_id', $flat->id)
                            ->where('maintenance_id', $maintenance->id)
                            ->first();
                    }
            
                    // If maintenance_id was not passed (new record) or payment doesn't exist (changing from Pending to Active), create new payment
                    if (!$maintenance_payment) {
                        $maintenance_payment = new MaintenancePayment();
                    }

                    $maintenance_payment->maintenance_id = $maintenance->id;
                    $maintenance_payment->building_id = $maintenance->building_id;
                    $maintenance_payment->flat_id = $flat->id;
                    $maintenance_payment->user_id = $flat->tanent ? $flat->tanent_id : $flat->owner_id;
                    $maintenance_payment->living_status = $flat->living_status;
                    $maintenance_payment->paid_amount = 0;
                    $maintenance_payment->dues_amount = $maintenance->amount;
                    $maintenance_payment->bill_no = 'MFIB' . rand(100000, 999999);

                    if ($maintenance->maintenance_type == 'Areawise') {
                        if ($flat->living_status == 'Vacant') {
                            $maintenance_payment->dues_amount = $flat->area * $request->vacant_amount;
                        } else {
                            $maintenance_payment->dues_amount = $flat->area * $request->amount;
                        }
                    } else {
                        if ($flat->living_status == 'Vacant') {
                            $maintenance_payment->dues_amount = $request->vacant_amount;
                        } else {
                            $maintenance_payment->dues_amount = $request->amount;
                        }
                    }

                    $maintenance_payment->late_fine = 0;
                    $maintenance_payment->type = 'Created';
                    $maintenance_payment->status = 'Unpaid';
                    $maintenance_payment->save();
                }
            }
        }
        
        $building = $user->building;

        // Only send notifications when maintenance is newly set to Active (handles both new and Pending→Active transitions)
        if ($maintenance->status === 'Active' && $oldStatus !== 'Active') {
             if ($request->maintenance_id) {
                // If transitioning from Pending to Active, treat as new
                if ($oldStatus === 'Pending') {
                    $title = 'New Maintenance Payment Added';
                    $body = "A new maintenance payment has been added by the Accounts. Please check the details and complete the payment.";
                } else {
                    $title = 'Maintenance Payment Updated';
                    $body = "An upcoming Maintenance Payment has been updated. Tap to view the latest information.";
                }
            } else {
                $title = 'New Maintenance Payment Added';
                $body = "A new maintenance payment has been added by the Accounts. Please check the details and complete the payment.";
            }

            $flats = $building->flats()->where('sold_out', 'Yes')->get();

            foreach ($flats as $flat) {
                // Find maintenance payment record for this flat
                $mp = MaintenancePayment::where('maintenance_id', $maintenance->id)
                    ->where('flat_id', $flat->id)
                    ->first();

                // If the payment already exists and is Paid, skip notifying this flat
                if ($mp && $mp->status === 'Paid') {
                    continue;
                }

                // Collect both owner and tenant if available
                $recipients = collect();

                if ($flat->owner) {
                    $recipients->push($flat->owner);
                }

                if ($flat->tanent) {
                    $recipients->push($flat->tanent);
                }

                foreach ($recipients as $targetUser) {
                    $dataPayload = [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'screen' => 'MaintenancePage',
                        'params' => json_encode([
                            'maintenanceId' => $maintenance->id,
                            'flat_id' => $flat->id,
                            'building_id' => $flat->building->id,
                            'user_id' => $targetUser->id,
                        ]),
                        'categoryId' => 'MaintenanceUpdate',
                        'channelId' => 'default',
                        'sound' => 'default',
                        'type' => 'MAINTENANCE_ADDED',
                        'user_id' => (string) $targetUser->id,
                        'flat_id' => (string) $flat->id,
                        'building_id' => (string) $maintenance->building_id,
                        'maintenance_id' => (string) $maintenance->id,
                    ];

                    NotificationHelper::sendNotification(
                        $targetUser->id,
                        $title,
                        $body,
                        $dataPayload,
                        [
                            'from_id' => $targetUser->id,
                            'flat_id' => $flat->id,
                            'building_id' => $flat->building_id,
                            'type' => 'maintenance_added',
                            'apns_client' => $this->apnsClient ?? null,
                            'ios_sound' => $dataPayload['sound'],
                        ],
                        ['user']
                    );
                }
            }
        }

        return response()->json(['msg' => $msg], 200);
    }
    
//   public function add_new_maintenance(Request $request)
//     {
//         $rules = [
//             'maintenance_id' => 'nullable|exists:maintenances,id',
//             'from_date' => 'required',
//             'to_date' => 'required',
//             'amount' => 'required',
//             'vacant_amount' => 'required',
//             'maintenance_type' => 'required|in:Areawise,Custom',
//             'due_date' => 'required',
//             'late_fine_type' => 'required|in:Percentage,Daily,Fixed',
//             'late_fine_value' => 'required',
//             'gst' => 'required',
//             'status' => 'required|in:Active,Pending',
//         ];
    
//         $msg = 'Maintenance added Susccessfully';
//         $maintenance = new Maintenance();
//         if($request->maintenance_id) {
//             $maintenance = Maintenance::withTrashed()->find($request->maintenance_id);
//             $msg = 'Maintenance updated Susccessfully';
//         }
    
//         $validation = \Validator::make($request->all(), $rules);
    
//         if ($validation->fails()) {
//             return response()->json([
//                 'error' => $validation->errors()->first()
//             ],422);
//         }
//         $user = Auth::User();
//         $maintenance->user_id = $user->id;
//         $maintenance->building_id = $user->building_id;
//         $maintenance->from_date = $request->from_date;
//         $maintenance->to_date = $request->to_date;
//         $maintenance->maintenance_created_by=Auth::User()->id;
//         $maintenance->amount = $request->amount;
//         $maintenance->vacant_amount = $request->vacant_amount;
//         $maintenance->maintenance_type = $request->maintenance_type;
//         $maintenance->due_date = $request->due_date;
//         $maintenance->late_fine_type = $request->late_fine_type;
//         $maintenance->late_fine_value = $request->late_fine_value;
//         $maintenance->gst = $request->gst;
//         $maintenance->status = $request->status;
//         $maintenance->save();
        
//         if($request->status=='Active'){
//         foreach($user->building->flats as $flat)
//         {
//             if($flat->sold_out == 'Yes'){
//                 if($request->maintenance_id) {
//                     $maintenance_payment = MaintenancePayment::where('flat_id',$flat->id)
//                         ->where('maintenance_id',$maintenance->id)
//                         ->first();
        
//                     // If not found, skip this flat
//                     if (!$maintenance_payment) {
//                         continue;
//                     }
//                 }
        
//                 // If maintenance_id was not passed, create new payment
//                 if (!$request->maintenance_id) {
//                     $maintenance_payment = new MaintenancePayment();
//                 }
//                 $maintenance_payment->maintenance_id = $maintenance->id;
//                 $maintenance_payment->building_id = $maintenance->building_id;
//                 $maintenance_payment->flat_id = $flat->id;
//                 $maintenance_payment->user_id = $flat->tanent ? $flat->tanent_id : $flat->owner_id;
//                 $maintenance_payment->living_status = $flat->living_status;
//                 $maintenance_payment->paid_amount = 0;
//                 $maintenance_payment->dues_amount = $maintenance->amount;
//                 $maintenance_payment->bill_no = 'MFIB'.rand(100000,999999);
//                 if($maintenance->maintenance_type == 'Areawise'){
//                     if($flat->living_status == 'Vacant'){
//                         $maintenance_payment->dues_amount = $flat->area * $request->vacant_amount;
//                     }else{
//                         $maintenance_payment->dues_amount = $flat->area * $request->amount;
//                     }
//                 }else{
//                     if($flat->living_status == 'Vacant'){
//                         $maintenance_payment->dues_amount = $request->vacant_amount;
//                     }else{
//                         $maintenance_payment->dues_amount = $request->amount;
//                     }
//                 }
//                 $maintenance_payment->late_fine = 0;
//                 $maintenance_payment->type = 'Created';
//                 $maintenance_payment->status = 'Unpaid';
//                 $maintenance_payment->save();
                
//                 // Check if user has a device token
//                 $tanent = $flat->tanent;
//                 $owner = $flat->owner;
                
//                 $usersToNotify = collect([$tanent, $owner])->filter();
                
//     foreach ($usersToNotify as $targetUser) {
//     if ($targetUser) {

//         $title = 'New Maintenance Payment Added';
//         $body = 'A new maintenance payment has been added. Please check the details and complete the payment.';

//         $dataPayload = [
//             'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
//             'screen' => 'MaintenancePage',
//             'params' => json_encode([
//                 'maintenanceId' => $maintenance->id,
//                      'flat_id' => $flat->id,
//             ]),
//             'categoryId' => 'MaintenanceUpdate',
//             'channelId' => 'default',
//             'sound' => 'default',
//             'type' => 'MAINTENANCE_ADDED',
//             'user_id' => (string) $targetUser->id,
//             'flat_id' => (string) $flat->id,
//             'building_id' => (string) $maintenance->building_id,
//             'maintenance_id' => (string) $maintenance->id,
//         ];

//         // Send notification using helper
//         $notificationResult = NotificationHelper::sendNotification(
//             $targetUser->id, // Send to the flat owner/tenant
//             $title,
//             $body,
//             $dataPayload,
//             [
//                 'from_id' => $targetUser->id, // The BA or logged-in user who created maintenance
//                 'flat_id' => $flat->id,
//                 'building_id' => $maintenance->building_id,
//                 'type' => 'maintenance_added',
//                 'apns_client' => $this->apnsClient ?? null,
//                 'ios_sound' => 'default'
//       ],['user']
//         );
//     }
// }
// }
            
//         }
//         }
//         return response()->json([
//             'msg' => $msg,
//         ], 200);
    
//     }
    
    
    public function manage_maintenance(Request $request)
    {
        $user = Auth::user();
        $building = $user->building;
        $blocks = $building->blocks()->with(['flats'])->get();
        $flat_id = $request->flat_id;
    
        $query = MaintenancePayment::where('building_id', $building->id)
            ->whereIn('id', function ($subquery) use ($building) {
                $subquery->selectRaw('MAX(id)')
                    ->from('maintenance_payments')
                    ->where('building_id', $building->id)
                    ->groupBy('flat_id');
            });
    
        // Optional filter by flat_id
        if ($request->filled('flat_id') && $request->flat_id > 0) {
            $query->where('flat_id', $request->flat_id);
        }
    
        // Filter by maintenance's from_date
        if ($request->filled('from_date')) {
            $query->whereHas('maintenance', function ($q) use ($request) {
                $q->whereDate('from_date', '>=', $request->from_date);
            });
        }
    
        // Filter by maintenance's to_date
        if ($request->filled('to_date')) {
            $query->whereHas('maintenance', function ($q) use ($request) {
                $q->whereDate('to_date', '<=', $request->to_date);
            });
        }
    
        $maintenance_payments = $query->orderBy('created_at', 'desc')->with(['flat:id,name'])->get();
        //   $maintenance_payments = $query->orderBy('created_at', 'desc')->with(['flat','user','maintenance'])->get();
        $total_payment = 0;
        $total_gst = 0;
        foreach ($maintenance_payments as $payment) {
            $maintenance = $payment->maintenance;
            $late_fine = 0;

            if ($maintenance && $maintenance->due_date < now()) {
                $late_days = now()->diffInDays(Carbon::parse($maintenance->due_date));

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
        
        return response()->json([
            'maintenance_payments' => $maintenance_payments,
            'blocks' => $blocks,
            'flat_id' => $flat_id,
        ], 200);
    }
    
    public function pay_maintenance(Request $request)
    {
        $flat = Flat::where('id',$request->flat_id)->where('building_id',Auth::User()->building_id)->with([
        'building:id,name',
        'block:id,name',
        'owner',    // Add this
        'tanent'    // Add this
    ])->first();
        
        if(!$flat){
            return response()->json([
                'msg' => 'Flat not found',
            ], 422);
        }
        
if ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant') {
    $user = $flat->owner;
} else {
    $user = $flat->tenant;
}

        
        $last_payment = MaintenancePayment::where('flat_id', $flat->id)
            ->where('status', 'Paid')
            ->orderBy('id', 'desc')
            ->first();
        $last_paid_date = $last_payment ? $last_payment->created_at->format('Y-m-d') : 'N/A';

        $maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
            ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building:id,name'])
            
            ->where('status', 'Unpaid')
            ->orderBy('id', 'desc')
            ->get();

        $total_payment = 0;
        $total_gst = 0;
        foreach ($maintenance_payments as $payment) {
            $maintenance = $payment->maintenance;
            $late_fine = 0;

            if ($maintenance && $maintenance->due_date < now()) {
                $late_days = now()->diffInDays(Carbon::parse($maintenance->due_date));

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

        $transactions = Transaction::where('model','Maintenance')->where('flat_id',$request->flat_id)->with(['maintenance_payments.maintenance','user','payerRole'])->orderBy('id','desc')->get();
        return response()->json([
                'flat' => $flat,
                'maintenance_payments' => $maintenance_payments,
                'transactions' => $transactions,
                'total_payment' => $total_payment,
                'gst' => $gst,
                'grand_total' => $grand_total,
                'last_paid_date' => $last_paid_date,
                'user' => $user,
        ], 200);
    }
    
       public function pay_maintenance_bill(Request $request)
    {
        $flat = Flat::where('id',$request->flat_id)->where('building_id',Auth::User()->building_id)->first();
        
        if(!$flat){
            return response()->json([
                'error' => 'Flat not found',
            ], 422);
        }
        if($flat->tanent){
            $user = $flat->tanent;
        }else{
            $user = $flat->owner;
        }
        $userNew=User::where('id',$request->user_id)->first();
        
     
        $last_payment = MaintenancePayment::where('flat_id', $flat->id)
            ->where('status', 'Paid')
            ->orderBy('id', 'desc')
            ->first();
        $last_paid_date = $last_payment ? $last_payment->created_at->format('Y-m-d') : 'N/A';

        $maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
            ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
            ->where('status', 'Unpaid')
            ->orderBy('id', 'desc')
            ->get();
        if($maintenance_payments->isEmpty()){
            return response()->json([
                'error' => 'No maintenance payment found',
            ], 422);
        }
        $total_payment = 0;
        $total_gst = 0;
        foreach ($maintenance_payments as $payment) {
            $maintenance = $payment->maintenance;
            $late_fine = 0;

            if ($maintenance && $maintenance->due_date < now()) {
                $late_days = now()->diffInDays(Carbon::parse($maintenance->due_date));

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
        // $paid_maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
        //     ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building','transaction'])
        //     ->where('status', 'Paid')
        //     ->orderBy('id', 'desc')
        //     ->get();
        $transactions = Transaction::where('user_id',$userNew->id)->where('model','Maintenance')->with(['user','maintenance_payments.maintenance'])->orderBy('id','desc')->get();
        if(ceil($request->amount) != ceil($grand_total)){
            return response()->json([
                'error' => 'Paying amount doesnt match to current bill',
            ], 422);
        }
            $transaction = new Transaction();
            $transaction->building_id = Auth::User()->building_id;
            $transaction->user_id = $userNew->id;
            
           $transaction->flat_id = $flat->id;
          $transaction->block_id = $flat->block_id;
                                    
            $transaction->order_id = '';
            $transaction->model = 'Maintenance';
            $paid_maintenance = MaintenancePayment::where('flat_id',$flat->id)->orderBy('id','desc')->first();
            $transaction->model_id = $paid_maintenance->maintenance_id;
            $transaction->type = 'Credit';
            $transaction->payment_type = $request->payment_type;
            $transaction->amount = $request->amount;
            $transaction->reciept_no = rand();
            $transaction->desc = 'Maintenance Payment paid by flat number '.$flat->name .' through Accountant : '.Auth::User()->name;
            $transaction->status = 'Success';
            $transaction->payerrole_id = Auth::User()->id;
            $transaction->date = now()->toDateString();
            $transaction->save();
            
            $maintenance_payment = $paid_maintenance;
            $maintenance_payment->user_id = $userNew->id;
            $maintenance_payment->paid_amount = $maintenance_payment->dues_amount;
            $maintenance_payment->paid_date = now()->toDateString();
            $maintenance_payment->dues_amount = 0;
            $maintenance_payment->type = 'Credit';
            $maintenance_payment->payment_type = $request->payment_type;
            $maintenance_payment->desc = 'Maintenance Payment paid by flat number '.$flat->name .' through Accountant : '.Auth::User()->name;
            $maintenance_payment->transaction_id = $transaction->id;
            $maintenance_payment->status = 'Paid';
            $maintenance_payment->save();

            $maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
            // ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
            ->where('status', 'Unpaid')
            ->orderBy('id', 'desc')
            ->get();
            
            foreach($maintenance_payments as $maintenance_payment){
                $maintenance_payment->paid_amount = $maintenance_payment->dues_amount;
                $maintenance_payment->paid_date = now()->toDateString();
                $maintenance_payment->dues_amount = 0;
                $maintenance_payment->type = 'Credit';
                $maintenance_payment->transaction_id = $transaction->id;
                
                $maintenance_payment->payment_type = $request->payment_type;
                $maintenance_payment->desc = 'Paid through Accountant ';
                $maintenance_payment->transaction_id = $transaction->id;
                $maintenance_payment->status = 'Paid';
                $maintenance_payment->save();
            }
            
       // Check if user has a device token
$tanent = $flat->tanent;
$owner = $flat->owner;

$usersToNotify = collect([$tanent, $owner])->filter();

foreach ($usersToNotify as $targetUser) {
    if ($targetUser) {

        $title = 'Maintenance Payment Successful';
        $body = 'A new maintenance payment has been paid by the Accounts. Please check the details.';

        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'MaintenancePage',
            'params' => json_encode([
                'maintenanceId' => $maintenance->id,
            ]),
            'categoryId' => 'MaintenanceUpdate',
            'channelId' => 'default',
            'sound' => 'default',
            'type' => 'MAINTENANCE_PAID',
            'user_id' => (string) $targetUser->id,
            'flat_id' => (string) $flat->id,
            'building_id' => (string) $maintenance->building_id,
            'maintenance_id' => (string) $maintenance->id,
        ];

        // Use NotificationHelper to send notification
        $notificationResult = NotificationHelper::sendNotification(
            $targetUser->id, // Send to tenant or owner
            $title,
            $body,
            $dataPayload,
            [
                'from_id' => $targetUser->id, // The user triggering this payment
                'flat_id' => $flat->id,
                'building_id' => $maintenance->building_id,
                'type' => 'maintenance_paid',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'default',
   ],['user']
        );
    }
}

        return response()->json([
            'success' => 'Payment recieved successfully',
        ], 200); 

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
        $maintenance_payment = MaintenancePayment::where('id',$maintenance_payment_id)->where('building_id',Auth::User()->building_id)->first();
        if(!$maintenance_payment){
            return response()->json([
                'error' => 'Maintenance payment not found'
            ],422);
        }
        // dd($maintenance_payment);
        $flat = $maintenance_payment->flat;
        
        // dd($flat);
         $payment_person = $request->get('payment_person');
                if($payment_person == 'owner' && $flat->owner){
                    $user = $flat->owner;
                }elseif($payment_person == 'tenant' && $flat->tanent){
                    $user = $flat->tanent;
                }else{
                    if($flat->tanent){
                        $user = $flat->tanent;
                    }else{
                        $user = $flat->owner;
                    }
        }
        // dd($flat->owner);
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
        // dd($maintenance);
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
                          if($payment->status == 'Paid'){
                            $late_fine = ($payment->paid_amount * $maintenance->late_fine_value) / 100;
                        }else{
                            $late_fine = ($payment->dues_amount * $maintenance->late_fine_value) / 100;
                        }
                        // $late_fine = ($payment->dues_amount * $maintenance->late_fine_value) / 100;
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
        //  dd($grand_total);
        // dd($user);
        $transactions = Transaction::where('user_id',$user->id)->where('model','Maintenance')->with(['user','maintenance_payments.maintenance'])->orderBy('id','desc')->get();
        // dd($transactions);
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
        $maintenance_payment = MaintenancePayment::where('id',$maintenance_payment_id)->where('building_id',Auth::User()->building_id)->first();

        if(!$maintenance_payment){
            return response()->json([
                'error' => 'Maintenance payment not found'
            ],422);
        }
        $flat = $maintenance_payment->flat;
        $payment_person = $request->get('payment_person');
                if($payment_person == 'owner' && $flat->owner){
                    $user = $flat->owner;
                }elseif($payment_person == 'tenant' && $flat->tanent){
                    $user = $flat->tanent;
                }else{
                    if($flat->tanent){
                        $user = $flat->tanent;
                    }else{
                        $user = $flat->owner;
                    }
        }
        
        
        $last_payment = MaintenancePayment::where('flat_id', $flat->id)
            ->where('status', 'Paid')
            ->where('id', '<', $maintenance_payment_id)
            ->orderBy('id', 'desc')
            ->first();
        $last_paid_date = $last_payment ? $last_payment->created_at->format('Y-m-d') : 'N/A';
        
        $transaction = $maintenance_payment->transaction;
        $maintenance_payments = $transaction->maintenance_payments()
            ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building.treasurer', 'flat.building.user', 'flat.building.city'])
            ->orderBy('id', 'desc')
            ->get();

        $total_payment = 0;
        $total_gst = 0;
        foreach ($maintenance_payments as $payment) {
            $maintenance = $payment->maintenance;
            $late_fine = 0;

            if ($maintenance && $maintenance->due_date < now()) {
                $late_days = now()->diffInDays(Carbon::parse($maintenance->due_date));

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
        
         $pdf = Pdf::loadView('partials.reciept.maintenance', [
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
    
//     public function add_new_event(Request $request)
//     {
//         $rules = [
//             'name' => 'required|max:65',
//             'desc' => 'required|max:200',
//             'from_time' => 'required',
//             'to_time' => 'required',
//             'is_payment_enabled' => 'required|in:Yes,No',
//             'status' => 'required|in:Active,Pending,Inactive',
//             'image' => 'nullable|image|max:2048',
//         ];
    
//         $msg = 'Event added Susccessfully';
//         $event = new Event();
        
//             $title = 'New Event Added';
//             $body = 'A new event has been added. Please check the details and participate.';
    
//         if ($request->event_id) {
//             $event = Event::withTrashed()->find($request->event_id);
            
//             $msg = 'Event updated Susccessfully';
//             $title = 'Event Details Updated';
//             $body = "An upcoming event has been updated. Tap to view the latest information.";
            
//         }
    
//         $validation = \Validator::make($request->all(), $rules);
    
//         if ($validation->fails()) {
//             return response()->json([
//                 'error' => $validation->errors()->first()
//             ],422);
//         }
        
//         $user = Auth::user();
//         $building = $user->building;
        
//         if ($request->hasFile('image')) {
//             $file = $request->file('image');
//             $allowedfileExtension = ['jpeg', 'jpg', 'png'];
//             $extension = $file->getClientOriginalExtension();
//             if (!empty($event->photo_filename)) {
//                 $file_path = public_path('images/' . $event->image_filename);
            
//                 if (is_file($file_path)) {
//                     unlink($file_path);
//                 }
//             }

//             $filename = 'events/' . uniqid() . '.' . $extension;
//             $path = $file->move(public_path('/images/events/'), $filename);
//             $event->image = $filename;
//         }
        
//         $event->building_id = $building->id;
//         $event->name = $request->name;
//         $event->created_by = Auth::user()->id;
//         $event->created_by_rolename = 'Accounts';
//         $event->desc = $request->desc;
//         $event->from_time = $request->from_time;
//         $event->to_time = $request->to_time;
//         $event->is_payment_enabled = $request->is_payment_enabled;
//         $event->status = $request->status;
        
//         if ($event->actived_by === null) {
//         if ($request->status === 'Active') {
//         $event->actived_by = $user->id;
//         }
//     }else if($request->id && $request->status === 'Active'){
//         // $updated_notification="Yes";
//         //             $title = 'Essential Payment Updated';
//         //             $body = 'The essential payment status has been updated. If you have already completed the payment, please ignore this message.';
//     }
    
//         $event->save();
   
// //         foreach ($building->flats as $flat) {
// //     $tenant = $flat->tanent;
// //     $owner = $flat->owner;

// //     // Collect both tenant and owner if they exist
// //     $usersToNotify = collect([$tenant, $owner])->filter();

// //     foreach ($usersToNotify as $targetUser) {
// //         if ($targetUser) {
// //             $dataPayload = [
// //                 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
// //           'screen' => 'Timeline',
// //     'params' => json_encode([
// //         'ScreenTab' => 'Events',
// //         'event_id' => $event->id,
// //                 'flat_id'=>$flat->id,
// //                 'user_id' =>$targetUser->id,
// //                 'building_id'=>$building->id
// //                 ]),
// //                 'categoryId' => '',
// //                 'channelId' => '',
// //                 'sound' => 'bellnotificationsound.wav',
// //                 'type' => 'EVENT_ADDED',
// //                 'user_id' => (string) $targetUser->id,
// //             ];

// //             // ✅ Use your custom Notification Helper
// //             NotificationHelper::sendNotification(
// //                 $targetUser->id, // recipient user id
// //                 $title,
// //                 $body,
// //                 $dataPayload,
// //                 [
// //                     'from_id' => $targetUser->id,
// //                     'flat_id' => $flat->id,
// //                     'building_id' => $flat->building_id,
// //                     'type' => 'event_added', // or 'corpus_fund_update' based on context
// //                     'ios_sound' => 'default'
// //                 ]
// //             );
// //         }
// //     }
// // }

    
//         return response()->json([
//                 'msg' => $msg,
//                 '$request->from_time'=>$request->from_time
//             ],200);
//     }

  public function add_new_event(Request $request)
    {
        // Permission check
        if (!Auth::User()->role == 'BA' && !Auth::User()->hasRole('accounts') && !Auth::User()->hasPermission('custom.events')) {
            return response()->json(['error' => 'Permission denied!'], 403);
        }

        $rules = [
            'name' => 'required|max:65',
            'desc' => 'required|max:200',
            'from_time' => 'required|date' . ($request->event_id ? '' : '|after_or_equal:now'),
            'to_time' => 'required|date|after:from_time',
            'is_payment_enabled' => 'required|in:Yes,No',
            'status' => 'required|in:Active,Pending,Inactive',
            'image' => 'nullable|image|max:2048',
        ];

        $msg = 'Event added Successfully';
        $event = new Event();
        $oldStatus = null;
        $oldPayment = null;

        if ($request->event_id) {
            $event = Event::withTrashed()->find($request->event_id);
            $oldStatus = $event->status;
            $oldPayment = $event->is_payment_enabled ?? 'No';
            $msg = 'Event updated Successfully';
        }

        $validation = \Validator::make($request->all(), $rules, [
            'name.required' => 'Event name is required.',
            'desc.required' => 'Event details are required.',
            'from_time.after_or_equal' => 'From time cannot be in the past.',
            'to_time.after' => 'To Date & Time cannot be earlier than From Date & Time.'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->first()], 422);
        }

        $user = Auth::user();
        $building = $user->building;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            
            if (!empty($event->image_filename)) {
                $file_path = public_path('images/' . $event->image_filename);
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'events/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/events/'), $filename);
            $event->image = $filename;
        }

        $event->building_id = $building->id;
        $event->name = $request->name;
        $event->desc = $request->desc;
        $event->created_by = Auth::user()->id;
        $event->created_by_rolename = 'Accounts';
        $event->from_time = $request->from_time;
        $event->to_time = $request->to_time;
        $event->is_payment_enabled = $request->is_payment_enabled;
        $event->status = $request->status;

        // Handle status activation
        if ($request->status === 'Active') {
            $event->actived_by = Auth::id();
        } else {
            // $event->actived_by = null;
            if (property_exists($event, 'start_notified_at') || array_key_exists('start_notified_at', $event->getAttributes())) {
                $event->start_notified_at = null;
            }
        }

        $event->save();

        // Handle notifications based on status changes
        $statusChanged = $oldStatus && $oldStatus !== 'Active' && $request->status === 'Active';
        $isNewActiveEvent = !$oldStatus && $request->status === 'Active';

        if ($isNewActiveEvent || $statusChanged) {
            Log::info("Event activation triggered from API", [
                'event_id' => $event->id,
                'is_new' => !$oldStatus,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'activated_by' => Auth::id(),
            ]);
            $this->sendEventNotificationImmediately($event);
        }

        // Handle payment change: if payment changed from No -> Yes, trigger notifications
        if ($oldPayment !== null && $oldPayment === 'No' && ($request->is_payment_enabled ?? $event->is_payment_enabled) === 'Yes') {
            try {
                $now = Carbon::now();
                $eventStart = $event->from_time ? Carbon::parse($event->from_time) : null;

                if ($event->status === 'Active') {
                    Log::info('Payment enabled for already Active event (API) - sending immediate payment-enabled notification', ['event_id' => $event->id]);
                    $this->sendEventPaymentEnabledNotification($event);
                } elseif ($eventStart && $eventStart->lessThanOrEqualTo($now)) {
                    if ($event->status !== 'Active') {
                        $event->status = 'Active';
                        $event->actived_by = Auth::id();
                        $event->save();
                        Log::info('Event made Active due to payment enabled by Accounts (API)', ['event_id' => $event->id]);
                    }
                    Log::info('Event payment enabled and start time reached (API) - sending activation notifications', ['event_id' => $event->id]);
                    $this->sendEventNotificationImmediately($event);
                } else {
                    Log::info('Event payment enabled but start time not reached (API); sending payment-enabled notification', ['event_id' => $event->id, 'from_time' => $event->from_time]);
                    $this->sendEventPaymentEnabledNotification($event);
                }
            } catch (\Exception $e) {
                Log::error('Failed handling payment-enabled notification/activation (API)', ['event_id' => $event->id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json(['msg' => $msg], 200);
    }
    
    
       
    public function add_new_eventxxx(Request $request)
    {
        $rules = [
            'name' => 'required|max:65',
            'desc' => 'required|max:200',
            'from_time' => 'required',
            'to_time' => 'required',
            'is_payment_enabled' => 'required|in:Yes,No',
            'status' => 'required|in:Active,Pending,Inactive',
            'image' => 'nullable|image|max:2048',
        ];
    
        $msg = 'Event added Susccessfully';
        $event = new Event();
        
            $title = 'New Event Added';
            $body = 'A new event has been added. Please check the details and participate.';
    
        if ($request->event_id) {
            $event = Event::withTrashed()->find($request->event_id);
            
            $msg = 'Event updated Susccessfully';
            $title = 'Event Details Updated';
            $body = "An upcoming event has been updated. Tap to view the latest information.";
            
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ],422);
        }
        
        $user = Auth::user();
        $building = $user->building;
        
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $allowedfileExtension = ['jpeg', 'jpg', 'png'];
            $extension = $file->getClientOriginalExtension();
            if (!empty($event->photo_filename)) {
                $file_path = public_path('images/' . $event->image_filename);
            
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }

            $filename = 'events/' . uniqid() . '.' . $extension;
            $path = $file->move(public_path('/images/events/'), $filename);
            $event->image = $filename;
        }
        
        $event->building_id = $building->id;
        $event->name = $request->name;
        $event->created_by = Auth::user()->id;
        $event->created_by_rolename = 'Accounts';
        $event->desc = $request->desc;
        $event->from_time = $request->from_time;
        $event->to_time = $request->to_time;
        $event->is_payment_enabled = $request->is_payment_enabled;
        $event->status = $request->status;
        
        if ($event->actived_by === null) {
        if ($request->status === 'Active') {
        $event->actived_by = $user->id;
        }
    }else if($request->id && $request->status === 'Active'){
        // $updated_notification="Yes";
        //             $title = 'Essential Payment Updated';
        //             $body = 'The essential payment status has been updated. If you have already completed the payment, please ignore this message.';
    }
    
        $event->save();
   
//         foreach ($building->flats as $flat) {
//     $tenant = $flat->tanent;
//     $owner = $flat->owner;

//     // Collect both tenant and owner if they exist
//     $usersToNotify = collect([$tenant, $owner])->filter();

//     foreach ($usersToNotify as $targetUser) {
//         if ($targetUser) {
//             $dataPayload = [
//                 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
//           'screen' => 'Timeline',
//     'params' => json_encode([
//         'ScreenTab' => 'Events',
//         'event_id' => $event->id,
//                 'flat_id'=>$flat->id,
//                 'user_id' =>$targetUser->id,
//                 'building_id'=>$building->id
//                 ]),
//                 'categoryId' => '',
//                 'channelId' => '',
//                 'sound' => 'bellnotificationsound.wav',
//                 'type' => 'EVENT_ADDED',
//                 'user_id' => (string) $targetUser->id,
//             ];

//             // ✅ Use your custom Notification Helper
//             NotificationHelper::sendNotification(
//                 $targetUser->id, // recipient user id
//                 $title,
//                 $body,
//                 $dataPayload,
//                 [
//                     'from_id' => $targetUser->id,
//                     'flat_id' => $flat->id,
//                     'building_id' => $flat->building_id,
//                     'type' => 'event_added', // or 'corpus_fund_update' based on context
//                     'ios_sound' => 'default'
//                 ]
//             );
//         }
//     }
// }

    
        return response()->json([
                'msg' => $msg,
                '$request->from_time'=>$request->from_time
            ],200);
    }
    public function get_events(Request $request)
    {
        $building = Auth::User()->building;
        $events = $building->events;
        return response()->json([
            'events' => $events
        ],200);
    }
    
    public function event_paymentsxxx(Request $request)
    {
        $rules = [
            'event_id' => 'required|exists:events,id',
        ];
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ],422);
        }
        $building = Auth::User()->building;
        $event = Event::where('id',$request->event_id)->with(['payments.user','payments.flat:id,name',
        'payments.transaction'])->first();
        return response()->json([
            'event' => $event
        ],200);
    }
    public function event_payments(Request $request)
{
    $rules = [
        'event_id' => 'required|exists:events,id',
    ];

    $validation = \Validator::make($request->all(), $rules);

    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first()
        ], 422);
    }

    $building = Auth::user()->building;

    $event = Event::where('id', $request->event_id)
        ->with(['payments' => function($query) {
            $query->with(['user', 'flat:id,name', 'transaction'])
                  ->orderBy('created_at', 'desc'); // sort by latest first
        }])
        ->first();

    return response()->json([
        'event' => $event
    ], 200);
}

    
    public function store_event_payment(Request $request)
    {
        $rules = [
            'event_id' => 'required|exists:events,id',
            'user_id' => 'required|exists:users,id',
            'flat_id' => 'required',
            'payment_type' => 'required|in:InHand,InBank',
            'amount' => 'required',
            'status' => 'required|in:Paid',
        ];
        $event = Event::find($request->event_id);
        $user = User::find($request->user_id);
        $msg = 'Payment added Susccessfully';
        $payment = new Payment();
    
        if ($request->id) {
            $payment = Payment::withTrashed()->find($request->id);
            $msg = 'Payment added Susccessfully';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->first()
            ],200);
        }
        
        $payment->event_id = $request->event_id;
        $payment->building_id = Auth::User()->building_id;
        $payment->user_id = $request->user_id;
        $payment->type = 'Credit';
        $payment->payment_type = $request->payment_type;
        $payment->amount = $request->amount;
        $payment->date = $request->date;
        $payment->status = $request->status;
        $payment->flat_id = $request->flat_id;
       
        $payment->save();

            $transaction = new Transaction();
            $transaction->building_id = Auth::User()->building_id;
            $transaction->user_id = $request->user_id;
            $transaction->model = 'Event';
            $transaction->model_id = $request->event_id;
            $transaction->type = 'Credit';
            $transaction->payment_type = $request->payment_type;
            $transaction->amount = $request->amount;
            $transaction->reciept_no = (string) $payment->id .'-RCP-'.rand(100000,999999);
            $transaction->payerrole_id = Auth::User()->id;
            // $transaction->block_id = ;
            $transaction->desc = 'Event Payment for '.$event->name.'  paid by '.$user->name.' through Accountant : '.Auth::User()->name;
            $transaction->status = 'Success';
            $transaction->date = $request->date;
            $transaction->flat_id = $request->flat_id;
            $transaction->save();
            
        $payment->transaction_id = $transaction->id;
        $payment->save();
        
// Check if user has a device token
$title = 'Event Payment Successful';
$body = 'Your payment of Rs. ' . $request->amount . ' for the event "' . $event->name . '" was successful. You can now download your payment receipt.';



$dataPayload = [
    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
    'screen' => 'EventsFundList',
    'params' => json_encode(['eventID' => $event->id]),
    'categoryId' => '',
    'channelId' => '',
    'sound' => 'bellnotificationsound.wav',
    'type' => 'EVENT_PAID',
    'user_id' => (string)$user->id,
];

// Send notification using helper
$notificationResult = NotificationHelper::sendNotification(
    $request->user_id,
    $title,
    $body,
    $dataPayload,
    [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $request->flat_id,
                'building_id' => Auth::User()->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'default'
            ]
);

        return response()->json([
            'success' => $msg,
            '$notificationResult'=>$notificationResult,
            '$user'=>$user,

        ],200);
    }
    
public function get_user_by_email(Request $request)
{
    $user = User::where(function($q) use ($request) {
                $q->where('email', $request->email)
                  ->orWhere('phone', $request->email);
            })
            ->where('status', 'Active')
            ->first();

    if ($user) {

        // Fetch flats with building + block info
        $flats = DB::table('flats')
            ->join('buildings', 'buildings.id', '=', 'flats.building_id')
            ->leftJoin('blocks', 'blocks.id', '=', 'flats.block_id')
            ->select(
                'flats.id as flat_id',
                'flats.name as flat_name',
                'flats.area',
                'flats.living_status',
                'flats.status as flat_status',
                'flats.owner_id',
                'flats.tanent_id',
                'buildings.id as building_id',
                'buildings.name as building_name',
                'blocks.id as block_id',
                'blocks.name as block_name'
            )
            ->where(function($q) use ($user) {
                $q->where('flats.owner_id', $user->id)
                  ->orWhere('flats.tanent_id', $user->id);
            })
            ->where('flats.status', 'Active')
            ->get();

        // Optional: building links if needed
        $buildingLinks = DB::table('building_users')
            ->join('buildings', 'buildings.id', '=', 'building_users.building_id')
            ->select(
                'building_users.id as building_user_id',
                'building_users.user_id',
                'buildings.id as building_id',
                'buildings.name as building_name'
            )
            ->where('building_users.user_id', $user->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => trim($user->first_name . ' ' . $user->last_name),
                'flats_data' => $flats,
                'building_links' => $buildingLinks,
            ]
        ], 200);
    }

    return response()->json(['success' => false, 'message' => 'User not found'], 422);
}



    
        public function get_user_by_emailXxxxx(Request $request) {
        $user = User::where('email', $request->email)->orWhere('phone', $request->email)->where('status','Active')->first();
        if ($user) {
            return response()->json(['success' => true, 'data' => ['id' => $user->id,'name' => $user->name]],200);
        }
        return response()->json(['success' => false, 'message' => 'User not found'],422);
    }
    
    public function event_reciept(Request $request)
    {
        $payment = Payment::where('id',$request->payment_id)->where('building_id',Auth::User()->building_id)->first();

        if(!$payment){
            return response()->json([
                'error' => 'Event payment not found'
            ]);
        }
        $pdf = Pdf::loadView('partials.reciept.event', [
            'payment' => $payment,
        ]);

        return $pdf->download('event-reciept.pdf');
    }
    
    public function get_essentials(Request $request)
    {
        $building = Auth::User()->building;
        $essentials = $building->essentials;
        
        return response()->json([
            'essentials' => $essentials
        ],200);
    }
    
 public function store_essential(Request $request)
{
    $rules = [
        'essential_id' => 'nullable|exists:essentials,id',
        'reason' => 'required',
        'amount' => 'required',
        'status' => 'required|in:Active,Inactive,Pending',
        'due_date' => 'required',
        'late_fine_type' => 'required|in:Percentage,Daily,Fixed',
        'late_fine_value' => 'required|int',
        'gst' => 'required|int',
    ];

    $validation = \Validator::make($request->all(), $rules);

    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first()
        ]);
    }

    $msg = 'Essential added Successfully';
    $essential = new Essential();

    if ($request->essential_id) {
        $essential = Essential::withTrashed()->find($request->essential_id);
        $msg = 'Essential updated Successfully';
    }

    $user = Auth::user();

    // Save base essential data
    $essential->user_id = $user->id;
    $essential->building_id = $user->building_id;
    $essential->reason = $request->reason;
    $essential->amount = $request->amount;
    $essential->due_date = $request->due_date;
    $essential->late_fine_type = $request->late_fine_type;
    $essential->late_fine_value = $request->late_fine_value;
    $essential->gst = $request->gst;
    $essential->status = $request->status;
    
            if ($essential->actived_by === null) {
        if ($essential->status === 'Active') {
        $essential->actived_by = $user->id;
        }
    }else if($request->id && $request->status === 'Active'){
        // $updated_notification="Yes";
        //             $title = 'Essential Payment Updated';
        //             $body = 'The essential payment status has been updated. If you have already completed the payment, please ignore this message.';
    }
    $essential->save();

    // -----------------------------------------
    // ✔️ Only process payments if status = Active
    // -----------------------------------------
    if ($request->status === "Active") {

        foreach ($user->building->flats as $flat) {

            if ($flat->owner_id) {

                $essential_payment = null;

                // If editing existing essential
                if ($request->essential_id) {
                    $essential_payment = EssentialPayment::where('essential_id', $essential->id)
                        ->where('flat_id', $flat->id)
                        ->where('status', 'Unpaid')
                        ->first();
                }

                if (!$essential_payment) {
                    $essential_payment = new EssentialPayment();
                }

                $essential_payment->essential_id = $essential->id;
                $essential_payment->building_id = $essential->building_id;
                $essential_payment->flat_id = $flat->id;
                $essential_payment->user_id = $flat->owner_id;
                $essential_payment->paid_amount = 0;
                $essential_payment->dues_amount = $essential->amount;
                $essential_payment->bill_no = 'ESSE-' . rand(100000, 999999);
                $essential_payment->type = 'Created';
                $essential_payment->status = 'Unpaid';
                $essential_payment->save();

                // Send notification only in Active status
                $owner = $flat->owner;

                if ($owner) {
                    $title = 'New Essential Payment Added';
                    $body = 'A new essential payment has been added. Please review the details.';

                    $dataPayload = [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'screen' => 'EssentialsList',
                        'params' => json_encode(['essentialsID' => $essential->id]),
                        'type' => 'ESSENTIAL_ADDED',
                        'user_id' => (string) $owner->id,
                        'flat_id' => (string) $flat->id,
                        'building_id' => (string) $essential->building_id,
                        'essential_id' => (string) $essential->id,
                    ];

                    NotificationHelper::sendNotification(
                        $owner->id,
                        $title,
                        $body,
                        $dataPayload,
                        [
                            'from_id' => $owner->id,
                            'flat_id' => $flat->id,
                            'building_id' => $essential->building_id,
                            'type' => 'maintenance_added',
                            'apns_client' => $this->apnsClient ?? null,
                        ],
                        ['user']
                    );
                }
            }
        }
    }

    return response()->json([
        'msg' => $msg,
        '$essential->status'=>$essential->id,
          '$essential->actived_by'=>$essential->actived_by,
    ], 200);
}


    

    
    
    public function essential_payments(Request $request)
    {
    $rules = [
        'essential_id' => 'required|exists:essentials,id',
    ];

    $validation = \Validator::make($request->all(), $rules);

    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first()
        ]);
    }

    $building = Auth::user()->building;

    $essential = Essential::where('id', $request->essential_id)
        ->where('building_id', $building->id)
        ->with(['payments.user:id,first_name,last_name,email,phone,photo','payments.flat:id,name,block_id','payments.transaction'])
        ->first();

    if (!$essential) {
        return response()->json([
            'error' => 'Essential not found'
        ]);
    }

    foreach ($essential->payments as $essential_payment) {
        $dues_amount = $essential_payment->dues_amount;
        $late_fine = 0;
        $gst = $essential_payment->essential->gst;

        if ($essential_payment && $essential_payment->essential->due_date < now()) {
            $late_days = now()->diffInDays(Carbon::parse($essential_payment->essential->due_date));

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
        $grand_total = $total_amount + $total_gst;

        // Add grand_total attribute dynamically to the payment model
        $essential_payment['grand_total'] = ceil($grand_total);
    }

    return response()->json([
        'essential' => $essential
    ], 200);
}

    
 public function store_essential_payment(Request $request)
{
    $rules = [
        'essential_payment_id' => 'required|exists:essential_payments,id',
        'amount' => 'required',
        'payment_type' => 'required|in:InHand,InBank',
        'status' => 'required|in:Paid',
    ];

    $msg = 'Essential payment added Successfully';
    $essential_payment = new EssentialPayment();

    if ($request->essential_payment_id) {
        $essential_payment = EssentialPayment::where('id', $request->essential_payment_id)->withTrashed()->first();
        $msg = 'Essential payment updated Successfully';
    }

    $validation = \Validator::make($request->all(), $rules);

    if ($validation->fails()) {
        return response()->json([
            'error' => $validation->errors()->first()
        ], 422);
    }

    $flat = $essential_payment->flat;

    if ($essential_payment->status == 'Paid') {
        return response()->json([
            'error' => 'Payment already completed'
        ], 422);
    }

    $dues_amount = $essential_payment->dues_amount;
    $late_fine = 0;
    $gst = $essential_payment->essential->gst;

    if ($essential_payment && $essential_payment->essential->due_date < now()) {
        $late_days = now()->diffInDays(Carbon::parse($essential_payment->essential->due_date));

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
    $grand_total = $total_amount + $total_gst;

    if ($request->amount != ceil($grand_total)) {
        return response()->json([
            'error' => 'Essential payment amount does not match the current bill'
        ], 422);
    }

    $essential_payment->paid_amount = $grand_total;
    $essential_payment->dues_amount = 0;
    $essential_payment->type = 'Credit';
    $essential_payment->payment_type = $request->payment_type;
    $essential_payment->date = now()->toDateString();
    $essential_payment->status = 'Paid';
    $essential_payment->save();

    $user = $flat->owner;

    $transaction = new Transaction();
    $transaction->building_id = Auth::user()->building_id;
    $transaction->user_id = $user->id;
    $transaction->order_id = '';
    
    $transaction->flat_id = $flat->id;
    $transaction->block_id = $flat->block_id;

    $transaction->model = 'Essential';
    $transaction->model_id = $essential_payment->essential_id;
    $transaction->type = 'Credit';
    $transaction->payment_type = $request->payment_type;
    $transaction->amount = $request->amount;
    $transaction->reciept_no = rand();
    $transaction->desc = 'Essential Payment paid by flat number ' . $user->flat->name . ' through Accounts : ' . Auth::user()->name;
    $transaction->status = 'Success';
    $transaction->date = now()->toDateString();
    $transaction->save();

    $essential_payment->transaction_id = $transaction->id;
    $essential_payment->save();

      $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'EssentialsList',
            'params' => json_encode([
                'essentialsID' => $essential_payment->essential_id,
                'flat_id'=>$flat->id,
                'building_id'=>Auth::user()->building_id,
                ]),
            'categoryId' => 'Essentials',
            'channelId' => 'default',
            'sound' => 'default',
            'type' => 'ESSENTIAL_ADDED',
        ];
        
        
        $title = 'Essential Payment Successful';
        $body = 'Your payment for essential was successful. You can now download your payment receipt.';

        
    // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $user->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $user->flat->id,
                // 'building_id' => $issue->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'default'
            ]
    );
    
  

    return response()->json([
        'msg' => $msg
    ], 200);
}

    
    public function essential_invoice(Request $request)
    {
        $payment_id = $request->essential_payment_id;
        $essential_payment = EssentialPayment::where('id',$payment_id)->where('building_id',Auth::User()->building_id)->first();
        
        if(!$essential_payment){
            return response()->json([
                'error' => 'Essential payment not found'
            ],422);
        }
        $flat = $essential_payment->flat;
        if($flat->tanent){
            $user = $flat->tanent;
        }else{
            $user = $flat->owner;
        }

        $dues_amount = $essential_payment->essential->amount;
        $late_fine = 0;
        $gst = $essential_payment->essential->gst;
        if ($essential_payment && $essential_payment->essential->due_date < now()) {
            $late_days = now()->diffInDays(Carbon::parse($essential_payment->essential->due_date));
            switch ($essential_payment->essential->late_fine_type) {
                case 'Daily':
                    $late_fine = $late_days * $essential_payment->essential->late_fine_value;
                    break;
                case 'Fixed':
                    $late_fine = $essential_payment->essential->late_fine_value;
                    break;
                case 'Percentage':
                    if($essential_payment->status == 'Paid'){
                        $late_fine = ($essential_payment->paid_amount * $essential_payment->essential->late_fine_value) / 100;
                    }else{
                        $late_fine = ($dues_amount * $essential_payment->essential->late_fine_value) / 100;
                    }
                    break;
                }
        }
        $payment = $essential_payment;
        $total_amount = $dues_amount + $late_fine;
        $total_gst = $total_amount * $gst / 100;
        $grand_total = $total_amount + $total_gst;
        $pdf = Pdf::loadView('partials.invoice.essential', [
            'flat' => $flat,
            'payment' => $payment,
            'late_fine' => $late_fine,
            'total_gst' => $total_gst,
            'grand_total' => $grand_total,
            'user' => $user,
        ]);

        return $pdf->download('invoice.pdf');
    }
    
    public function essential_reciept(Request $request)
    {
        $payment_id = $request->essential_payment_id;
        $essential_payment = EssentialPayment::where('id',$payment_id)->where('building_id',Auth::User()->building_id)->first();
        
        if(!$essential_payment){
            return response()->json([
                'error' => 'Essential payment not found'
            ], 422);
            
        }
        $flat = $essential_payment->flat;
        if($flat->tanent){
            $user = $flat->tanent;
        }else{
            $user = $flat->owner;
        }

        $dues_amount = $essential_payment->essential->amount;
        $late_fine = 0;
        $gst = $essential_payment->essential->gst;
        if ($essential_payment && $essential_payment->essential->due_date < now()) {
            $late_days = now()->diffInDays(Carbon::parse($essential_payment->essential->due_date));
            switch ($essential_payment->essential->late_fine_type) {
                case 'Daily':
                    $late_fine = $late_days * $essential_payment->essential->late_fine_value;
                    break;
                case 'Fixed':
                    $late_fine = $essential_payment->essential->late_fine_value;
                    break;
                case 'Percentage':
                    if($essential_payment->status == 'Paid'){
                        $late_fine = ($essential_payment->paid_amount * $essential_payment->essential->late_fine_value) / 100;
                    }else{
                        $late_fine = ($essential_payment->dues_amount * $essential_payment->essential->late_fine_value) / 100;
                    }
                    break;
                }
        }
        $payment = $essential_payment;
        $total_amount = $dues_amount + $late_fine;
        $total_gst = $total_amount * $gst / 100;
        $grand_total = $total_amount + $total_gst;
        
        $pdf = Pdf::loadView('partials.reciept.essential', [
            'flat' => $flat,
            'payment' => $payment,
            'late_fine' => $late_fine,
            'total_gst' => $total_gst,
            'grand_total' => $grand_total,
            'user' => $user,
        ]);

        return $pdf->download('reciept-essential.pdf');
        
    }
    
    public function get_bookings(Request $request)
    {
        $user = Auth::user();
        $building = $user->building;
    
        $query = Booking::where('building_id', $building->id)
            ->with(['user.flat','timing', 'facility']);
    
        // Apply from_date and to_date filters if provided
        if ($request->filled('from_date') && $request->filled('to_date')) {
            try {
                $from = Carbon::parse($request->from_date)->startOfDay();
                $to = Carbon::parse($request->to_date)->endOfDay();
    
                $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'error' => 'Invalid date format.'], 422);
            }
        }
    
        $bookings = $query->get()
            ->groupBy('reciept_no')
            ->map(function ($groupedBookings, $recieptNo) {
                return [
                    'reciept_no' => $recieptNo,
                    'bookings' => $groupedBookings->map(function ($booking) {
                        $booking->timing->dates = is_string($booking->timing->dates)
                            ? json_decode($booking->timing->dates, true)
                            : $booking->timing->dates;
                        return $booking;
                    }),
                    'transaction' => optional($groupedBookings->first()->transaction),
                    'order' => optional($groupedBookings->first()->order),
                    'facility' => optional($groupedBookings->first()->facility),
                ];
            })
            ->values();
    
        // $facilities = $building->facilities()->select(['id', 'name'])->get();
    
        return response()->json([
            'bookings' => $bookings
        ], 200);
    }


    
    public function get_facilities(Request $request)
    {
        $building = Auth::User()->building;
        $facilities = $building->facilities()->select(['id', 'name'])->get();
        return response()->json([
            'facilities' => $facilities
        ], 200);
    }


    
    public function society_fund(Request $request)
    {
        $user = Auth::user();
        $building = $user->building;
    
        $transactionsQuery = Transaction::where('building_id', $building->id);
    
        // Filter by model and model_id
        if ($request->filled('model')) {
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
    
        $transactions = $transactionsQuery->get();
        
        // Initialize totals
        $total_debit = 0;
        $total_credit = 0;
        $inhand = 0;
        $inbank = 0;
    
        foreach ($transactions as $transaction) {
            if ($transaction->type === 'Debit') {
                $total_debit += $transaction->amount;
            } elseif ($transaction->type === 'Credit') {
                $total_credit += $transaction->amount;
            }
    
            if ($transaction->payment_type === 'InHand') {
                $inhand += ($transaction->type === 'Credit' ? $transaction->amount : -$transaction->amount);
            } elseif ($transaction->payment_type === 'InBank') {
                $inbank += ($transaction->type === 'Credit' ? $transaction->amount : -$transaction->amount);
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

     private function sendEventNotificationImmediately(Event $event)
    {
        try {
            $building = $event->building;
            if (!$building) {
                Log::warning("Event notification skipped: no building found", ['event_id' => $event->id]);
                return;
            }

            // Check if event start time is in the future
            $now = Carbon::now();
            $eventStartTime = Carbon::parse($event->from_time);
            
            if ($eventStartTime > $now) {
                Log::info("Event notification deferred to future", [
                    'event_id' => $event->id,
                    'event_name' => $event->name,
                    'from_time' => $event->from_time,
                    'current_time' => $now->toDateTimeString(),
                ]);
                return;
            }

            // Get all users in the building
            $userIds = User::where('building_id', $building->id)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            // Include flat owners and tenants
            $flatOwnerTenantIds = \App\Models\Flat::where('building_id', $building->id)
                ->pluck('owner_id')
                ->merge(\App\Models\Flat::where('building_id', $building->id)->pluck('tanent_id'))
                ->filter()
                ->unique()
                ->toArray();

            $userIds = array_unique(array_merge($userIds, $flatOwnerTenantIds));
            $users = User::whereIn('id', $userIds)->whereNull('deleted_at')->get();

            Log::info("Sending event notification immediately", [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'building_id' => $building->id,
                'user_count' => $users->count(),
            ]);

            $title = "Event Updated";
            $body = 'New event: ' . $event->desc;
            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'Timeline',
                'params' => json_encode([
                    'ScreenTab' => 'Events',
                    'event_id' => $event->id
                ]),
                'categoryId' => 'Events',
                'channelId' => 'Community',
                'type' => 'EVENT_ACTIVATED',
            ];

            $sentCount = 0;
            foreach ($users as $user) {
                try {
                    $result = NotificationHelper::sendNotification(
                        $user->id,
                        $title,
                        $body,
                        $dataPayload,
                        [
                            'from_id' => Auth::id(),
                            'building_id' => $building->id,
                            'type' => 'event_activated',
                        ]
                    );
                    if ($result) {
                        $sentCount++;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send event notification to user", [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $event->start_notified_at = Carbon::now();
            $event->save();

            Log::info("Event notifications completed", [
                'event_id' => $event->id,
                'sent_count' => $sentCount,
            ]);
        } catch (\Exception $e) {
            Log::error("Event notification job failed", [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send a notification to users informing them that payment for an event was enabled.
     */
    private function sendEventPaymentEnabledNotification(Event $event)
    {
        try {
            $building = $event->building;
            if (!$building) {
                Log::warning("Event payment notification skipped: no building found", ['event_id' => $event->id]);
                return;
            }

            $userIds = User::where('building_id', $building->id)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            $flatOwnerTenantIds = \App\Models\Flat::where('building_id', $building->id)
                ->pluck('owner_id')
                ->merge(\App\Models\Flat::where('building_id', $building->id)->pluck('tanent_id'))
                ->filter()
                ->unique()
                ->toArray();

            $userIds = array_unique(array_merge($userIds, $flatOwnerTenantIds));
            $users = User::whereIn('id', $userIds)->whereNull('deleted_at')->get();

            Log::info("Sending event payment-enabled notification", [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'building_id' => $building->id,
                'user_count' => $users->count(),
            ]);

            $title = 'Event Payment Updated';
            $body = 'Payment is now available for your event. Please check the event details.';
            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'Timeline',
                'params' => json_encode([
                    'ScreenTab' => 'Events',
                    'event_id' => $event->id
                ]),
                'categoryId' => 'Events',
                'channelId' => 'Community',
                'type' => 'EVENT_PAYMENT_ENABLED',
            ];

            $sentCount = 0;
            foreach ($users as $user) {
                try {
                    $result = NotificationHelper::sendNotification(
                        $user->id,
                        $title,
                        $body,
                        $dataPayload,
                        [
                            'from_id' => Auth::id(),
                            'building_id' => $building->id,
                            'type' => 'event_payment_enabled',
                        ]
                    );
                    if ($result) {
                        $sentCount++;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send event payment notification to user", [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info("Event payment notifications completed", [
                'event_id' => $event->id,
                'sent_count' => $sentCount,
            ]);
        } catch (\Exception $e) {
            Log::error("Event payment notification job failed", [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
}
