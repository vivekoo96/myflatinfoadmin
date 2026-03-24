<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Facility;
use App\Models\Expense;
use App\Models\Transaction;
use App\Models\Setting;

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

class FacilityClosed extends Command
{

    protected $signature = 'facility:closed';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
        
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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $facilities = Facility::where('status', 'Closed')->get();

        foreach ($facilities as $facility) {
            // Fetch all upcoming bookings for this facility
            // $bookings = Booking::where('facility_id', $facility->id)
            //     ->whereHas('timing', function ($q) {
            //         $q->where('from', '>', Carbon::now()); // only future bookings
            //     })
            //     ->whereIn('status', ['Success', 'Created']) // active bookings
            //     ->get();
                
                  $bookings = [];

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
                    'screen' => 'FacilitiesCancelBookingsPage',
                    'params' => json_encode(['reciept_no' => $booking->reciept_no,'booking_id' => $booking->id]),
                    'categoryId' => $booking->facility_id,
                    'channelId' => 'security',
                    'sound' => 'bellnotificationsound.wav',
                    'type' => 'UNPLANNED_VISITOR_COMPLETED',
                    'user_id' => (string)$user->id,
                    'flat_id' => (string)$flat->id,
                    'building_id' => (string)$flat->building_id,
                ];
                // $title = 'Booking Id [' .$booking->reciept_no.'] has been cancelled by Building Admin';
                       $title = 'Receipt [' .$booking->reciept_no.'] has been cancelled by Building Admin';
                $body = $facility->closing_reason;
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
                                $expense->reason = 'Booking Cancelled';
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
                                $transaction->desc = 'Booking Cancelled';
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
                            $expense->reason = 'Booking Cancelled';
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
                            $transaction->desc = 'Booking Cancelled';
                            $transaction->status = 'Refund';
                            $transaction->save();
                }
            
                $booking->status = 'Cancelled';
                $booking->save();
                
                
            }
        }
    }
}
