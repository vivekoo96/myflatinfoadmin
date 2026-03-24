<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Setting;
use \Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Factory;
use Pushok\Client as ApnsClient;
use Pushok\AuthProvider\Token as ApnsToken;
use Pushok\Notification as ApnsNotification;
use Pushok\Payload;
use Pushok\Payload\Alert;

use DB;
use \Session;
use Mail;
use \Str;
use \Log;
use Carbon\Carbon;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestException;

class PaymentController extends Controller
{
     public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            if ($user && $user->building && $user->building->donation_is_active === 'No') {
                return redirect()->back()->with('error', 'Event or Donation function is Inactive');
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
        //
    }


    public function create()
    {
        //
    }

     public function store(Request $request)
    {
        $rules = [
            'event_id' => 'required|exists:events,id',
            'user_id' => 'required|exists:users,id',
            'flat_id' => 'nullable|exists:flats,id',
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
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        
        $payment->event_id = $request->event_id;
        $payment->building_id = Auth::User()->building_id;
        $payment->user_id = $request->user_id;
        $payment->flat_id = $request->flat_id ?? null;
        $payment->type = 'Credit';
        $payment->payment_type = $request->payment_type;
        $payment->amount = $request->amount;
        $payment->date = $request->date;
        $payment->status = $request->status;
        $payment->save();

        $transaction = new Transaction();
            $transaction->building_id = Auth::User()->building_id;
            $transaction->user_id = $request->user_id;
            $transaction->model = 'Event';
            $transaction->model_id = $request->event_id;
            $transaction->type = 'Credit';
            $transaction->flat_id = $request->flat_id ?? null;
            $transaction->payment_type = $request->payment_type;
            $transaction->amount = $request->amount;
            $transaction->reciept_no = (string) $payment->id .'-RCP-'.rand(100000,999999);
            $transaction->desc = 'Event Payment for '.$event->name.'  paid by '.$user->name.' through BA';
            $transaction->status = 'Success';
            $transaction->date = $request->date;
            $transaction->save();
            
        $payment->transaction_id = $transaction->id;
        $payment->save();
                    
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
        $title = 'Event Payment Successful';
        $body = "Your payment of ₹$request->amount for the event was successful. You can now download your payment receipt.";

        
        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen' => 'EventsFundList',
            'params' => json_encode([
                'eventID' => $event->id,
                'flat'=>$request->flat_id,
            ]),
            'categoryId' => '',
            'channelId' => '',
            'sound' => 'bellnotificationsound.wav',
            'type' => 'EVENT_PAID',
            'user_id' => (string)$user->id,
        ];
        
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
                    ->setAlert($alert)
                    ->setSound('bellnotificationsound.wav')
                    ->setCustomValue('click_action', $dataPayload['click_action'])
                    ->setCustomValue('screen', $dataPayload['screen'])
                    ->setCustomValue('params', $dataPayload['params'])
                    ->setCustomValue('categoryId', $dataPayload['categoryId'])
                    ->setCustomValue('channelId', $dataPayload['channelId'])
                    ->setCustomValue('type', $dataPayload['type'])
                    ->setCustomValue('sound', $dataPayload['sound']);
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

        return redirect()->back()->with('success', $msg);
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
        $payment = Payment::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $payment->delete();
        }else{
            $payment->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
}
