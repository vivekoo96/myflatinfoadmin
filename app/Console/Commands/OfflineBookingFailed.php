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

class OfflineBookingFailed extends Command
{

    protected $signature = 'offline_booking:failed';

    protected $description = 'Command description';

    // public function __construct()
    // {
    //     parent::__construct();
        
    //     $rdata = Setting::findOrFail(1);
    //     $this->keyId = $rdata->razorpay_key;
    //     $this->keySecret = $rdata->razorpay_secret;
    //     $this->displayCurrency = 'INR';

    //     $this->api = new Api($this->keyId, $this->keySecret);
    //     $this->authProvider = ApnsToken::create([
    //         'key_id' => '4KAVV6FLG4',
    //         'team_id' => 'XY9Q57Z367',
    //         'app_bundle_id' => 'com.aits.myflatinfo.dev',
    //         'private_key_path' => storage_path('app/apns/MyFLATINFO.p8'),
    //         'private_key_secret' => null,
    //     ]);
        
    //     $this->apnsClient = new ApnsClient($this->authProvider, $production = true); // true = production
    // }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $bookings = Booking::where('status', 'Created')
            ->where('type','Offline')
            ->where('created_at', '<', now()->subMinutes(20))
            ->get();
        foreach($bookings as $booking){
            if($booking->type == 'Offline'){
                $booking->status = 'Failed';
                $booking->save();

                $user = $booking->user;
                $flat_id = $booking->flat_id;
                
                        Log::info('stage 1');
                $dataPayload = [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    
                    'screen' => 'FacilitiesMyFacilities',
                    'params' => json_encode([
                        'flat_id' => $flat_id,
                        'reciept_no' => $booking->reciept_no,
                        'booking_id' => $booking->id,
                        ]),
                    // 'categoryId' => $booking->facility_id,
                    'channelId' => 'security',
                    'sound' => 'bellnotificationsound.wav',
                    'type' => 'UNPLANNED_VISITOR_COMPLETED',
                ];
                     Log::info('stage 2');
                $title = 'Booking Id [' .$booking->id.'] has been failed';
                $body = 'Due to non response of BA your booking has been marked as Failed';
                     Log::info('stage 3');
                                    // Send notification using helper
    $notificationResult = NotificationHelper::sendNotification(
        $user->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' => $user->id,  // From the person accepting
                'flat_id' => $flat_id,
                // 'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ],
            ['user']
    );
     Log::info('stage 4');
                
                
            }
            
        }
    }
}
