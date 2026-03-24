<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Building;
use App\Models\Setting;
use App\Models\User;
use \Auth;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;
use Pushok\Client as ApnsClient;
use Pushok\AuthProvider\Token as ApnsToken;
use Pushok\Notification as ApnsNotification;
use Pushok\Payload;
use Pushok\Payload\Alert;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use DB;
use \Session;
use Mail;
use \Str;
use \Log;
use Carbon\Carbon;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestException;

class EventController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            if ($user && $user->building && $user->building->payment_is_active === 'Yes' && $user->building->donation_is_active === 'Yes' ) {
                if($user->building->razorpay_key === '' || $user->building->razorpay_secret === '')
                return redirect()->route('setting.index')->with('error', 'Razorpay key and secret is not yet set settings, please set that first and try again');
            }

            return $next($request);
        });
        
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

    public function index()
    {
        if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::user()->selectedRole->name == "Accounts" || Auth::User()->hasPermission('custom.events') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        return view('admin.event.index',compact('building'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
      
    {

          
       
       
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.events'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'name' => 'required|max:65',
            'desc' => 'required|max:200',
            'from_time' => 'required|date' . ($request->id ? '' : '|after_or_equal:now'),
            'to_time' => 'required|date|after:from_time',
            'is_payment_enabled' => 'required|in:Yes,No',
            'status' => 'required|in:Active,Pending,Inactive',
            'image' => 'nullable|image|max:2048',
        ];
    
        $msg = 'Event added Susccessfully';
        $event = new Event();
        $oldStatus = null;
        $oldPayment = null;

        if ($request->id) {
            $event = Event::withTrashed()->find($request->id);
            $oldStatus = $event->status;
            $oldPayment = $event->is_payment_enabled ?? 'No';
            $msg = 'Event updated Susccessfully';
        }
    
        $validation = \Validator::make($request->all(), $rules, [
            'name.required' => 'Event name is required.',
            'desc.required' => 'Venue details are required.',
            'from_time.after_or_equal' => 'From time cannot be in the past.',
            'to_time.after' => 'To Date & Time cannot be earlier than From Date & Time.'
        ]);

        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first())->withInput();
        }
        
        // if($request->hasFile('image')) {
        //     $file= $request->file('image');
        //     $allowedfileExtension=['jpeg','jpeg','png'];
        //     $extension = $file->getClientOriginalExtension();
        //     Storage::disk('s3')->delete($event->getImageFilenameAttribute());
        //     $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        //     $filename = 'images/events/' . uniqid() . '.' . $extension;
        //     Storage::disk('s3')->put($filename, file_get_contents($file));
        //     $event->image = $filename;
        // }
        
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
        
        $building = Auth::User()->building;
        
        $event->building_id = Auth::User()->building_id;
        $event->name = $request->name;
        $event->desc = $request->desc;
        $event->created_by = Auth::user()->id;
        $event->created_by_rolename = 'BA';
        $event->from_time = $request->from_time;
        $event->to_time = $request->to_time;
        $event->is_payment_enabled = $request->is_payment_enabled;
        $event->status = $request->status;
        
        // Ensure actived_by and start_notified_at are set correctly.
        // For Pending/Inactive events these should be null. For Active, set actived_by to the current user.
        // TC07: When setting to Inactive, these are nullified to prevent new notifications and mark as hidden.
        if ($request->status === 'Active') {
            $event->actived_by = Auth::id();
        } else {
            // For Pending or Inactive ensure these flags are null so notifications are not triggered
            $event->actived_by = null;
            if (property_exists($event, 'start_notified_at') || array_key_exists('start_notified_at', $event->getAttributes())) {
                $event->start_notified_at = null;
            }
        }
    
        $event->save();
        
        // TC02/TC03/TC06/TC07: Handle notifications based on status changes
        // For new events (TC02/TC03): send if status=Active
        // For existing events (TC06): send only if status changed from Pending/Inactive to Active
        // For TC07 (Inactive): do NOT send notifications (conditions will be false)
        $statusChanged = $oldStatus && $oldStatus !== 'Active' && $request->status === 'Active';
        $isNewActiveEvent = !$oldStatus && $request->status === 'Active';
        
        if ($isNewActiveEvent || $statusChanged) {
            Log::info("Event activation triggered", [
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
                    Log::info('Payment enabled for already Active event (store) - sending immediate payment-enabled notification', ['event_id' => $event->id]);
                    $this->sendEventPaymentEnabledNotification($event);
                } elseif ($eventStart && $eventStart->lessThanOrEqualTo($now)) {
                    if ($event->status !== 'Active') {
                        $event->status = 'Active';
                        $event->actived_by = Auth::id();
                        $event->save();
                        Log::info('Event made Active due to payment enabled by BA (store)', ['event_id' => $event->id]);
                    }
                    Log::info('Event payment enabled and start time reached (store) - sending activation notifications', ['event_id' => $event->id]);
                    $this->sendEventNotificationImmediately($event);
                } else {
                    Log::info('Event payment enabled but start time not reached (store); sending payment-enabled notification', ['event_id' => $event->id, 'from_time' => $event->from_time]);
                    $this->sendEventPaymentEnabledNotification($event);
                }
            } catch (\Exception $e) {
                Log::error('Failed handling payment-enabled notification/activation (store)', ['event_id' => $event->id, 'error' => $e->getMessage()]);
            }
        }
        

      
         
         
        // Notifications for event start/activation are handled by the scheduled command
        // to avoid duplicate sends; controller no longer sends start-time notifications.





// Send notification to all users
// foreach ($users as $user) {
//     $dataPayload = $dataPayloadBase;
//     $dataPayload['user_id'] = $user->id;
    
//     // Notification payload data
// $dataPayloadBase = [
//     'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
//     'screen' => 'Timeline',
//     'params' => json_encode([
//         'ScreenTab' => 'Events',
//         'event_id' => $event->id
//     ]),
//     'categoryId' => 'Events',
//     'channelId' => 'Community',
//     'sound' => 'bellnotificationsound.wav',
//     'type' => 'EVENT_ADDED',
// ];

//     $notificationResult = NotificationHelper::sendNotification(
//         $user->id,
//         $title,
//         $body,
//         $dataPayload,
//         [
//             'from_id' => $user->id,
//             'building_id' => $building->id,
//             'type' => 'event_added',
//             'apns_client' => $this->apnsClient ?? null,
//             'ios_sound' => $dataPayload['sound'],
//         ],  ['user']
//     );

//     if (!$notificationResult) {
//         \Log::warning("Failed to send event notification to user {$user->id}");
//     }
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
        //             ->setCustomValue('sound', $dataPayload['sound']);
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
    
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.events'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $user = Auth::User();
        $event = Event::where('id',$id)->where('building_id',$user->building_id)->withTrashed()->first();
        if(!$event){
            return redirect()->route('event.index');
        }
        return view('admin.event.show',compact('event'));
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
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.events'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $event = Event::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $event->delete();
        }else{
            $event->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function update_event_status(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('feature.event'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $event = Event::where('id',$request->id)->withTrashed()->first();
        $previous = $event->status;
        if($event->status == 'Active'){
            $event->status = 'Inactive';
        }else{
            $event->status = 'Active';
            // When toggling to Active via this endpoint, also set actived_by and send notifications
            $event->actived_by = Auth::id();
            // Trigger immediate notifications for the newly activated event (TC02/TC03 behavior)
            $this->sendEventNotificationImmediately($event);
        }
        $event->save();

        // Notifications for activation/start are handled by scheduled command.

        return response()->json([
            'msg' => 'success'
        ],200);
    }

    public function update_event_payment_status(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('feature.event'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $event = Event::where('id', $request->id)->withTrashed()->first();
        if (!$event) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['msg' => 'Event not found'], 404);
            }
            return redirect()->back()->with('error', 'Event not found');
        }

        $previousPayment = $event->is_payment_enabled ?? 'No';

        // Prefer explicit posted value if provided (from form submit). Otherwise, toggle.
        $newPayment = $request->has('is_payment_enabled') ? $request->input('is_payment_enabled') : ($previousPayment === 'Yes' ? 'No' : 'Yes');

        // If nothing changed, just redirect back (prevents duplicate notifications)
        if ($newPayment === $previousPayment) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['msg' => 'no_change'], 200);
            }
            return redirect()->back();
        }

        $event->is_payment_enabled = $newPayment;
        $event->save();

        // If payment was changed from No -> Yes, handle notifications/activation.
        // Special case: if the event is already Active, send the payment-enabled notification immediately
        // at the moment of update (user requested behavior).
        if ($previousPayment == 'No' && $event->is_payment_enabled == 'Yes') {
            try {
                $now = Carbon::now();
                $eventStart = $event->from_time ? Carbon::parse($event->from_time) : null;

                if ($event->status == 'Active') {
                    // Event is already active — send payment-enabled notification right away
                    Log::info('Payment enabled for already Active event - sending immediate payment-enabled notification', ['event_id' => $event->id]);
                    $this->sendEventPaymentEnabledNotification($event);
                } elseif ($eventStart && $eventStart->lessThanOrEqualTo($now)) {
                    // Event start time has passed but status not Active — make Active and trigger activation flow
                    if ($event->status != 'Active') {
                        $event->status = 'Active';
                        $event->actived_by = Auth::id();
                        $event->save();
                        Log::info('Event made Active due to payment enabled by BA', ['event_id' => $event->id]);
                    }

                    Log::info('Event payment enabled and start time reached - sending activation notifications', ['event_id' => $event->id]);
                    $this->sendEventNotificationImmediately($event);
                } else {
                    // Payment enabled but event not started yet. Inform users payment is available.
                    Log::info('Event payment enabled but start time not reached; sending payment-enabled notification', ['event_id' => $event->id, 'from_time' => $event->from_time]);
                    $this->sendEventPaymentEnabledNotification($event);
                }
            } catch (\Exception $e) {
                Log::error('Failed handling payment-enabled notification/activation', ['event_id' => $event->id, 'error' => $e->getMessage()]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['msg' => 'success'], 200);
        }

        return redirect()->back()->with('success', 'Payment status updated successfully');
    }


    /**
     * Send event notification to all users in the building immediately if event should be live now.
     * TC10B: If from_time <= now, sends notifications immediately
     * TC10: If from_time > now (future), does NOT send notifications - scheduled command will handle it
     * 
     * Sets start_notified_at timestamp after sending.
     */
    private function sendEventNotificationImmediately(Event $event)
    {
        try {
            $building = $event->building;
            if (!$building) {
                Log::warning("Event notification skipped: no building found", ['event_id' => $event->id]);
                return;
            }

            // TC10: Check if event start time is in the future
            // If so, don't send notification now - let scheduled command handle it
            $now = Carbon::now();
            $eventStartTime = Carbon::parse($event->from_time);
            
            if ($eventStartTime > $now) {
                Log::info("Event notification deferred to future", [
                    'event_id' => $event->id,
                    'event_name' => $event->name,
                    'from_time' => $event->from_time,
                    'current_time' => $now->toDateTimeString(),
                    'message' => 'Event starts in future - scheduled command will send notification at from_time',
                ]);
                return; // Don't send now; let scheduled command handle it at from_time
            }

            // TC10B: from_time <= now, so send notification immediately
            // Get all users in the building (owners, tenants, assigned staff, etc.)
            $userIds = User::where('building_id', $building->id)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            // Also include users from flats in this building (owners/tenants)
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
                'from_time' => $event->from_time,
                'building_id' => $building->id,
                'user_count' => $users->count(),
                'reason' => 'Event from_time is in past or now',
            ]);

            $title = "Event Status Updated";
            $body  = "Good news! The event '{$event->name}' has been activated and is now available.";
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
                            'apns_client' => $this->apnsClient ?? null,
                        ],
                        ['user']
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

            // Mark event as notified
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
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send a notification to users informing them that payment for an event was enabled.
     * This is triggered when BA updates is_payment_enabled from 'No' to 'Yes'.
     */
    private function sendEventPaymentEnabledNotification(Event $event)
    {
        try {
            $building = $event->building;
            if (!$building) {
                Log::warning("Event payment notification skipped: no building found", ['event_id' => $event->id]);
                return;
            }

            // Collect users similar to sendEventNotificationImmediately
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
            $body = 'Payment is now available for your event. The Building Admin has updated your payment option to Yes.';
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
                            'apns_client' => $this->apnsClient ?? null,
                        ],['user']
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
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
