<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Facility;
use App\Models\Expense;
use App\Models\Transaction;
use App\Models\Setting;
use App\Models\Timing;

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

use Kreait\Firebase\Exception\MessagingException;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Helpers\NotificationHelper2 as NotificationHelper;

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

class BookingController extends Controller
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
    
    public function index(Request $request)
    {
         if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "President" || Auth::user()->selectedRole->name == "Facility" || Auth::User()->hasPermission('custom.bookings') )
       
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $user = Auth::User();
        $building = $user->building;
        $query = Booking::where('building_id', $building->id);
        // Filter by model and model_id
        if ($request->filled('facility_id') && $request->facility_id != 'All') {
            $query->where('facility_id', $request->facility_id);
        }

        // Filter by from_date and to_date
        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $bookings = $query->orderBy('date','desc')->get();
        $facilities = $building->facilities;
        return view('admin.booking.index',compact('bookings','facilities'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
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
        //
    }
    
    public function change_booking_status(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('facility') || Auth::User()->hasPermission('custom.bookings') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $booking = Booking::find($request->booking_id);
        
        if($booking->type == 'Online'){
            if($booking->status == 'Success' && $request->status == 'Cancelled' || $booking->status == 'Cancel Request' && $request->status == 'Cancelled'){
                $building = $booking->building;
                $transaction = $booking->transaction;
                $transaction->status = 'Cancelled';
                $transaction->save();
                $refund_amount = $booking->refundable_amount;
                if($booking->timing->cancellation_type == 'Manual'){
                    $refund_amount = $request->refund_amount;
                }
                if($refund_amount == 0){
                    $booking->status = 'Cancelled';
                    $booking->refunded_amount = $refund_amount;
                    $booking->save();
                    return redirect()->back()->with('success',"Facility booking cancelled successfully.");
                }
                if($building->facility_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
                    return redirect()->back()->with('error',"We are unable to create a payment. Please check razorpay keys.");
                }
                
               \Log::info('Refund: Instantiating Razorpay Api', [
                    'building_id' => $building->id,
                    'razorpay_key' => $building->razorpay_key,
                    'razorpay_secret' => $building->razorpay_secret,
                    'booking_id' => $booking->id,
                    'refund_amount' => $refund_amount,
                ]);
                $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
                \Log::info('Refund: Razorpay Api instantiated', [
                    'api_object' => is_object($this->api),
                    'booking_id' => $booking->id,
                ]);
                // Refund through Razorpay
                try {
                    $order = $booking->order;
                    if ($order->payment_id) {
                        $payment = $this->api->payment->fetch($order->payment_id);
                        $refund_id = '';
                        if($request->payment_type == 'InBank'){
                            $refund = $payment->refund([
                                'amount' => $refund_amount * 100, // refund full amount
                            ]);
                            $refund_id = $refund->id;
                        }
        
                        // Optionally store refund ID
                        $order->refund_id = $refund_id;
                        $order->status = 'Refund';
                        $order->save();
                        
                        $expense = new Expense();
                        $expense->user_id = $order->user_id;
                        $expense->building_id = $order->building_id;
                        $expense->model = $order->model;
                        $expense->model_id = $order->model_id;
                        $expense->payment_type = $request->payment_type;
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
                        $transaction->payment_type = $request->payment_type;
                        $transaction->amount = $refund_amount;
                        $transaction->reciept_no = $booking->reciept_no;
                        $transaction->desc = 'Booking Cancelled for facility';
                        $transaction->status = 'Refund';
                        $transaction->save();
                        
                        $booking->refunded_amount = $refund_amount;
                        $booking->status = 'Cancelled';
                     
                        
                        
                        
                          $title = 'Your Booking Has Been Cancelled';
$body = 'Your booking for '. $booking->facility->name .' has been cancelled by '. Auth::user()->name .'.';

                        $booking->save();
        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'FacilitiesMyFacilities',
            
            'params' => json_encode([
                   'reciept_no'=>$transaction->reciept_no,
            'user_id' => $booking->user_id,
            'flat_id' => $booking->flat_id,
            'building_id' => $booking->building_id,
            ]),
            'categoryId' => 'UnplannedVisitorsReq',
            // 'channelId' => 'longring',
            // 'ios_sound' => 'longring.wav',
            'type' => 'PARCEL_CREATED',
        ];
        
    // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $booking->user_id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $booking->user_id,  // From the person accepting
                'flat_id' => $booking->flat_id,
                'building_id' => $booking->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                // 'ios_sound' => 'longring.wav'
            ],
            ['user']);  
                           return redirect()->back()->with('success',"Facility booking cancelled successfully.");
                        
                        
                    } else {
                        return redirect()->back()->with('error',"No Razorpay payment ID found for receipt: {$request->reciept_no}");
                    }
                } catch (BadRequestException $e) {
                    return redirect()->back()->with('error','Razorpay refund failed: ' . $e->getMessage());
                }
            }
        }else{
            if($booking->timing->booking_type == 'Free' && $request->status == 'Cancelled'){
                    $booking->status = 'Cancelled';
                    $booking->refunded_amount = 0;
                    $booking->save();
                    
                    return redirect()->back()->with('success',"Facility booking cancelled successfully.");
            }
            if($booking->status == 'Created' && $request->status == 'Success'){
                if($booking->amount == 0){
                    $booking->status = 'Success';
                    // $booking->payment_type = $request->payment_type;
                    $booking->save();
                    return redirect()->back()->with('success',"Facility booking confirmed successfully.");
                }
                $order = $booking->order;
                
                $expense = new Expense();
                $expense->user_id = $order->user_id;
                $expense->building_id = $order->building_id;
                $expense->model = $order->model;
                $expense->model_id = $order->model_id;
                $expense->payment_type = $request->payment_type;
                $expense->reason = 'Booking Confirmed';
                $expense->type = 'Credit';
                $expense->date = now()->toDateString();
                $expense->amount = $booking->paid_amount;
                $expense->save();
                        
                $transaction = new Transaction();
                $transaction->building_id = $order->building_id;
                $transaction->user_id = $order->user_id;
                $transaction->model = $order->model;
                $transaction->model_id = $order->model_id;
                $transaction->date = now()->toDateString();
                $transaction->type = 'Credit';
                $transaction->payment_type = $request->payment_type;
                $transaction->amount = $booking->paid_amount;
                $transaction->reciept_no = $booking->reciept_no;
                $transaction->desc = 'Booking Confirmed';
                $transaction->status = 'Success';
                $transaction->save();
                
                $booking->transaction_id = $transaction->id;
                $booking->payment_type = $request->payment_type;
                $booking->status = 'Success';
                $booking->paid_amount = $booking->payable_amount;
                $booking->paid_desc = 'Payment done throughout - '.Auth::User()->name.','.Auth::User()->phone;
                $booking->save();
                
                        
      $title = 'Booking Successful';
$body = 'Your booking for '. $booking->facility->name .' has been successfully confirmed by '. Auth::user()->name .'.';

        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'FacilitiesMyFacilities',
            
            'params' => json_encode([
                   'reciept_no'=>$transaction->reciept_no,
            'user_id' => $booking->user_id,
            'flat_id' => $booking->flat_id,
            'building_id' => $booking->building_id,
            ]),
            'categoryId' => 'UnplannedVisitorsReq',
            // 'channelId' => 'longring',
            // 'ios_sound' => 'longring.wav',
            'type' => 'PARCEL_CREATED',
        ];
        
    // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $booking->user_id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $booking->user_id,  // From the person accepting
                'flat_id' => $booking->flat_id,
                'building_id' => $booking->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                // 'ios_sound' => 'longring.wav'
            ],
            ['user']
    );
    
                return redirect()->back()->with('success',"Facility booking confirmed successfully.");
            }
            
            //
            if($booking->status == 'Success' && $request->status == 'Cancelled' || $booking->status == 'Cancel Request' && $request->status == 'Cancelled'){
                $order = $booking->order;
                
                $transaction = $booking->transaction;
                if($transaction){
                    $transaction->status = 'Cancelled';
                    $transaction->save();
                }
                $refund_amount = $booking->refundable_amount;
                if($booking->timing->cancellation_type == 'Manual'){
                    $refund_amount = $request->refund_amount;
                }
                if($refund_amount == 0){
                    $booking->refunded_amount = $refund_amount;
                    $booking->status = 'Cancelled';
                    $booking->save();
                    return redirect()->back()->with('success','Facility booking cancelled successfully');
                }
                        $expense = new Expense();
                        $expense->user_id = $order->user_id;
                        $expense->building_id = $order->building_id;
                        $expense->model = $order->model;
                        $expense->model_id = $order->model_id;
                        $expense->payment_type = $request->payment_type;
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
                        $transaction->payment_type = $request->payment_type;
                        $transaction->amount = $refund_amount;
                        // $transaction->reciept_no = $booking->reciept_no;
                        $transaction->desc = 'Booking Cancelled for facility';
                        $transaction->status = 'Refund';
                      
                        
                        $booking->refunded_amount = $refund_amount;
                        $booking->status = 'Cancelled';
                        $booking->payment_type = $request->payment_type;
                        $booking->refunded_desc = 'Refund payment done throughout - '.Auth::User()->name.','.Auth::User()->phone;
                        $booking->save();
                        
         
                        
                         $title = 'Your Booking Has Been Cancelled ';
        $body = 'Your booking for '. $booking->facility->name .' has been cancelled by the '. Auth::User()->name .'.';
        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'FacilitiesMyFacilities',
            
            'params' => json_encode([
            // 'ScreenTab' => 'Unplanned Visitors',
            'reciept_no'=>$transaction->reciept_no,
            // 'visitor_id' => $visitor->id,
            
            'user_id' => $booking->user_id,
            'flat_id' => $booking->flat_id,
            'building_id' => $booking->building_id,
            ]),
            'categoryId' => 'UnplannedVisitorsReq',
            // 'channelId' => 'longring',
            // 'ios_sound' => 'longring.wav',
            'type' => 'PARCEL_CREATED',
        ];
        
    // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $booking->user_id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $booking->user_id,  // From the person accepting
                'flat_id' => $booking->flat_id,
                'building_id' => $booking->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                // 'ios_sound' => 'longring.wav'
            ],
            ['user']
    );
    
      $transaction->save();
                        
                        return redirect()->back()->with('success','Facility booking cancelled successfully');
            }
        }
        return redirect()->back()->with('error','Condition not matched');
    }
    
    public function cancel_slot_booking(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('facility') || Auth::User()->hasPermission('custom.bookings') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $timing = Timing::find($request->timing_id);
        if(!$timing){
            return redirect()->back()->with('error','Timing slot not found');
        }
            $bookings = Booking::where('timing_id', $request->timing_id)->where('date',$request->date)
                ->whereHas('timing', function ($q) {
                    // $q->where('from', '>', Carbon::now()); // only future bookings
                })
                ->whereIn('status', ['Success', 'Created']) // active bookings
                ->get();

            foreach ($bookings as $booking) {
                $user = $booking->user;
                $flat = $user->flat;
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
                
                $dataPayload = [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'screen' => 'FacilitiesMyFacilities',
                    'params' => json_encode(['reciept_no' => $booking->reciept_no,'booking_id' => $booking->id]),
                    'categoryId' => $booking->facility_id,
                    'channelId' => 'security',
                    'sound' => 'bellnotificationsound.wav',
                    'type' => 'UNPLANNED_VISITOR_COMPLETED',
                    'user_id' => (string)$user->id,
                    'flat_id' => (string)$flat->id,
                    'building_id' => (string)$flat->building_id,
                ];
                $title = 'Booking Id [' .$booking->id.'] has been cancelled by Building Admin';
                $body = $request->reason;
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
                                    \Log::error("Notification sent");
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
                if($booking->type == 'Online'){
                    if($booking->status == 'Success'){
                        $building = $booking->building;
                        $transaction = $booking->transaction;
                        $transaction->status = 'Cancelled';
                        $transaction->save();
                        $refund_amount = $booking->paid_amount;
                        if($refund_amount == 0){
                            $booking->status = 'Cancelled';
                            $booking->save();
                            continue;
                        }
                        
                        if($booking->timing->booking_type == 'Free'){
                            $booking->status = 'Cancelled';
                            $booking->save();
                            continue;
                        }
                    
                        if($building->facility_is_active == 'No' || $building->razorpay_key == '' || $building->razorpay_secret == ''){
                            continue;
                        }
                        
                        $this->api = new Api($building->razorpay_key, $building->razorpay_secret);
                        // Refund through Razorpay
                        try {
                            $order = $booking->order;
                            if ($order->payment_id) {
                                $payment = $this->api->payment->fetch($order->payment_id);
                
                                // Issue full refund
                                $refund = $payment->refund([
                                    'amount' => $refund_amount * 100, // refund full amount
                                ]);
                
                                // Optionally store refund ID
                                $order->refund_id = $refund->id;
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
                                
                            } else {
                                continue;
                            }
                        } catch (BadRequestException $e) {
                            continue;
                        }
                    }
                }else{
                    if($booking->timing->booking_type == 'Free'){
                        $booking->status = 'Cancelled';
                        $booking->save();
                        
                        continue;
                    }
                    $order = $booking->order;
                    
                    $transaction = $booking->transaction;
                    if($transaction){
                        $transaction->status = 'Cancelled';
                        $transaction->save();
                    }
                    $refund_amount = $booking->paid_amount;
                    if($refund_amount == 0){
                        $booking->status = 'Cancelled';
                        $booking->save();
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
                }
            
                $booking->status = 'Cancelled';
              
                
                
            }
            $dates = json_decode($timing->dates, true) ?? [];

            // Remove the selected date
            $dates = array_filter($dates, function($d) use ($request) {
                return $d !== $request->date;
            });
        
            // Reindex the array and save back as JSON
            $timing->dates = json_encode(array_values($dates));
            $timing->save();
            
        $title = 'Your Booking Has Been Cancelled ';
        $body = 'Your booking for '. $booking->facility->name .' has been cancelled by the '. Auth::User()->name .'.';
        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'FacilitiesMyFacilities',
            
            'params' => json_encode([
            // 'ScreenTab' => 'Unplanned Visitors',
            // 'reciept_no'=>$transaction->reciept_no,
            // 'visitor_id' => $visitor->id,
            
            'user_id' => $booking->user_id,
            'flat_id' => $booking->flat_id,
            'building_id' => $booking->building_id,
            ]),
            'categoryId' => 'UnplannedVisitorsReq',
            'channelId' => 'bellnotificationsound',
            'ios_sound' => 'bellnotificationsound.wav',
            'type' => 'PARCEL_CREATED',
        ];
          $booking->save();
    // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $booking->user_id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $booking->user_id,  // From the person accepting
                'flat_id' => $booking->flat_id,
                'building_id' => $booking->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                // 'ios_sound' => 'longring.wav'
            ],
            ['user']
    );
    
    
            return redirect()->back()->with('success','Slot bookings cancelled successfully');
    }
}
