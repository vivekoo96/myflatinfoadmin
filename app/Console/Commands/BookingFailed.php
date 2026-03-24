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
use App\Helpers\NotificationHelper2 as NotificationHelper;
use Illuminate\Support\Arr;

class BookingFailed extends Command
{

    protected $signature = 'booking:failed';

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
        $bookings = Booking::where('status', 'Created')
            ->where('type','Online')
            ->where('created_at', '<', now()->subMinutes(5))
            //  ->where('created_at', '<', now()->subMinutes(1))
            ->get();
        foreach($bookings as $booking){
            if($booking->type == 'Online'){
                $booking->status = 'Failed';
                $booking->save();
            
                $user = $booking->user;
                $flat = $booking->flat_id;
                
                // $title = 'Booking Id [' .$booking->reciept_no.'] has been failed';
                 $title = 'Receipt [' .$booking->reciept_no.'] has been failed';
            
                $body = 'Due to uncomplete of payment your booking has been marked as Failed';
                
                
                                
                $dataPayload = [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'screen' => 'FacilitiesCancelBookingsPage',
                    'params' => json_encode([
                        'reciept_no' => $booking->reciept_no,
                        'booking_id' => $booking->id,
                        'flat_id'=>$flat->id,
                        ]),
                    'categoryId' => $booking->facility_id,
                    'channelId' => 'App',
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
            ],
            ['user']
    );

            
                // // Setup Firebase for Android/Web
                // $firebaseFactory = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
                // $firebaseMessaging = $firebaseFactory->createMessaging();
            
                // $apnsClient = $this->apnsClient;


                
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
                //         } catch (\Exception $e) {
                //             \Log::error("FCM error for token $token: " . $e->getMessage());
                //             $device->delete();
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
                //                     \Log::error("Notification sent");
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
                
            }
            
        }
    }
}
