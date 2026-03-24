<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Essential;
use App\Models\EssentialPayment;
use App\Models\Building;
use App\Models\Transaction;
use App\Models\MaintenancePayment;
use App\Models\Flat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use App\Helpers\NotificationHelper2 as NotificationHelper;

class EssentialController extends Controller
{

 public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            // Use allBuildings() helper for consistent building collection
            $buildings = method_exists($user, 'allBuildings') ? $user->allBuildings() : collect();
            if ($buildings->count() > 0) {
                foreach ($buildings as $building) {
                    if ($building->payment_is_active === 'Yes' && $building->other_is_active === 'Yes') {
                        if ($building->razorpay_key === '' || $building->razorpay_secret === '') {
                            return redirect()->route('setting.index')->with('error', 'Razorpay key and secret is not yet set in settings for one or more buildings, please set that first and try again');
                        }
                    }
                }
            } else if ($user && $user->building && $user->building->payment_is_active === 'Yes' && $user->building->other_is_active === 'Yes') {
                if ($user->building->razorpay_key === '' || $user->building->razorpay_secret === '') {
                    return redirect()->route('setting.index')->with('error', 'Razorpay key and secret is not yet set in settings, please set that first and try again');
                }
            }
            return $next($request);
        });
    }

    public function index()
    {
        
        // dd("weafwef");
       if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts') ||   (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President') || Auth::User()->hasPermission('custom.essentials') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        return view('admin.essential.index',compact('building'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.essentials'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            // 'building_id' => 'required|exists:buildings,id',
            'reason' => 'required',
            'amount' => 'required',
            'status' => 'required|in:Pending,Active,Inactive',
        ];
    
        $msg = 'Essential added Susccessfully';
        $essential = new Essential();
        $oldStatus = null;

        if ($request->id) {
            $essential = Essential::withTrashed()->find($request->id);
            $oldStatus = $essential ? $essential->status : null;
            $msg = 'Essential updated Susccessfully';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
              // Notification title & body
                    $title = 'New Essential Payment Added';
                    $body = 'A new essential payment has been added by the BA. Please review the details and proceed with the payment.';
                    
        $user = Auth::User();
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
        if ($request->status === 'Active') {
        $essential->actived_by = $user->id;
        }
    }else if($request->id && $request->status === 'Active'){
        $updated_notification="Yes";
                    $title = 'Essential Payment Updated';
                    $body = 'The essential payment status has been updated. If you have already completed the payment, please ignore this message.';
    }
        $essential->save();

        // Only create/update EssentialPayment records if status is Active
        if ($essential->status === 'Active') {
            foreach($user->building->flats as $flat)
            {
                if($flat->owner_id > 0){
                    $essential_payment = null;
                    
                    // Try to find existing unpaid payment
                    $essential_payment = EssentialPayment::where('essential_id',$essential->id)
                        ->where('flat_id',$flat->id)
                        ->where('status','Unpaid')
                        ->first();
                    
                    // If no payment found, create new one
                    if(!$essential_payment) {
                        $essential_payment = new EssentialPayment();
                        $essential_payment->essential_id = $essential->id;
                        $essential_payment->building_id = $essential->building_id;
                        $essential_payment->flat_id = $flat->id;
                        $essential_payment->user_id = $flat->owner_id;
                        $essential_payment->paid_amount = 0;
                        $essential_payment->dues_amount = $essential->amount;
                        $essential_payment->bill_no = 'ESSE-'.rand(100000,999999);
                        $essential_payment->type = 'Created';
                        $essential_payment->status = 'Unpaid';
                        $essential_payment->save();
                    } else {
                        // Update existing payment with new amount only
                        $essential_payment->dues_amount = $essential->amount;
                        $essential_payment->save();
                    }
                }
            }
        }
        
        // Send notifications for all flats if status is Active and was not Active before
        if ($essential->status === 'Active' && $oldStatus !== 'Active') {
            foreach($user->building->flats as $flat)
            {
                if($flat->owner_id > 0) {
                    $owner = $flat->owner;
                    $usersToNotify = collect([$owner])->filter();

                    // Only send notifications if the essential is Active now and was NOT Active before
                    foreach ($usersToNotify as $targetUser) {
                        // Build the payload data
                        $dataPayload = [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'screen' => 'EssentialsList',
                            'params' => json_encode(['essentialsID' => $essential->id,
                            'flat_id'=>$flat->id,
                            'building_id'=>$user->building_id
                            ]),
                            'categoryId' => '',
                            'channelId' => '',
                            'sound' => 'bellnotificationsound.wav',
                            'type' => 'ESSENTIAL_ADDED',
                            'user_id' => (string) $targetUser->id,
                        ];

                        // Send notification using your helper
                        $notificationResult = NotificationHelper::sendNotification(
                            $targetUser->id,
                            $title,
                            $body,
                            $dataPayload,
                            [
                                'from_id' => null,
                                'flat_id' => $flat->id,
                                'building_id' => $flat->building_id,
                                'type' => 'essential_added',
                                'apns_client' => $this->apnsClient ?? null,
                                'ios_sound' => $dataPayload['sound'],
                            ],['user']
                        );

                        // Optional: handle response or log if needed
                        if (!$notificationResult) {
                            Log::warning("Failed to send Essential Payment notification to user {$targetUser->id}");
                        }

                        // Persist notification to database (best-effort)
                        try {
                            DB::table('database_notifications')->insert([
                                'user_id' => $targetUser->id,
                                'from_id' => null,
                                'flat_id' => $flat->id,
                                'building_id' => $flat->building_id,
                                'title' => $title,
                                'body' => $body,
                                'type' => 'essential_added',
                                'dataPayload' => json_encode($dataPayload),
                                'status' => 1,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);
                        } catch (\Exception $ex) {
                            Log::error('Failed to save database notification for user '.$targetUser->id.': '.$ex->getMessage());
                        }
                    }
                }
            }
        }
    
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.essentials'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $user = Auth::User();
        $essential = Essential::where('id',$id)->where('building_id',$user->building_id)->withTrashed()->first();
        if(!$essential){
            return redirect()->route('essential.index');
        }
        return view('admin.essential.show',compact('essential'));
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
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.essentials'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $essential = Essential::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $essential->delete();
        }else{
            $essential->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function update_essential_status(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.essentials') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $essential = Essential::where('id',$request->id)->withTrashed()->first();
        $previous = $essential ? $essential->status : null;
        if($essential->status == 'Active'){
            $essential->status = 'Inactive';
        }else{
            $essential->status = 'Active';
        }
        $essential->save();

        // If toggled to Active and it was not Active before, send notifications
        try {
            if ($essential->status === 'Active' && $previous !== 'Active') {
                // Reuse the same notification logic as on create
                foreach($essential->building->flats as $flat) {
                    $owner = $flat->owner;
                    $usersToNotify = collect([$owner])->filter();
                    foreach ($usersToNotify as $targetUser) {
                        $dataPayload = [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'screen' => 'EssentialsList',
                            'params' => json_encode(['essentialsID' => $essential->id,'flat_id'=>$flat->id,'building_id'=>$essential->building_id]),
                            'categoryId' => '',
                            'channelId' => '',
                            'sound' => 'bellnotificationsound.wav',
                            'type' => 'ESSENTIAL_ACTIVATED',
                            'user_id' => (string) $targetUser->id,
                        ];

                        $title = 'Essential Activated';
                        $body = 'An essential payment has been activated by the BA.';

                        NotificationHelper::sendNotification(
                            $targetUser->id,
                            $title,
                            $body,
                            $dataPayload,
                            [
                                'from_id' => null,
                                'flat_id' => $flat->id,
                                'building_id' => $flat->building_id,
                                'type' => 'essential_activated',
                                'apns_client' => $this->apnsClient ?? null,
                                'ios_sound' => $dataPayload['sound'],
                            ]
                        );

                        try {
                            DB::table('database_notifications')->insert([
                                'user_id' => $targetUser->id,
                                'from_id' => null,
                                'flat_id' => $flat->id,
                                'building_id' => $flat->building_id,
                                'title' => $title,
                                'body' => $body,
                                'type' => 'essential_activated',
                                'dataPayload' => json_encode($dataPayload),
                                'status' => 1,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);
                        } catch (\Exception $ex) {
                            Log::error('Failed to save database notification for user '.$targetUser->id.': '.$ex->getMessage());
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error while sending essential activation notifications: '.$e->getMessage());
        }

        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function store_essential_payment(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.essentials') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $rules = [
            'essential_payment_id' => 'required|exists:essential_payments,id',
            'amount' => 'required',
            'payment_type' => 'required|in:InHand,InBank',
            'status' => 'required|in:Paid',
        ];
    
        $msg = 'Essential payment added Susccessfully';
        $essential_payment = new EssentialPayment();
    
        if ($request->essential_payment_id) {
            $essential_payment = EssentialPayment::withTrashed()->find($request->essential_payment_id);
            $msg = 'Essential payment updated Susccessfully';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        
        $flat = $essential_payment->flat;
        
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
        $payment = $essential_payment;
        $total_amount = $dues_amount + $late_fine;
        $total_gst = $total_amount * $gst / 100;
        $grand_total = $total_amount + $total_gst;
        
        if($request->amount != $grand_total){
            return redirect()->back()->with('error','Essential payment amount does not match to current bill');
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
        $transaction->building_id = Auth::User()->building_id;
        $transaction->user_id = $user->id;
         $transaction->flat_id = $flat->id;
          $transaction->block_id = $flat->block->id;
          
        $transaction->order_id = '';
        $transaction->model = 'Essential';
        $paid_maintenance = MaintenancePayment::where('flat_id',$flat->id)->orderBy('id','desc')->first();
        $transaction->model_id = $request->essential_payment_id;
        $transaction->payerrole_id = Auth::User()->id;
        $transaction->type = 'Credit';
        $transaction->payment_type = $request->payment_type;
        $transaction->amount = $request->amount;
        $transaction->reciept_no = rand();
        $transaction->desc = 'Essential Payment paid by flat number '.$flat->name .' Through BA: '.Auth::User()->name;
        $transaction->status = 'Success';
        $transaction->date = now()->toDateString();
        $transaction->save();
        
        $essential_payment->transaction_id = $transaction->id;
        $essential_payment->save();
        
        // Check if user has a device token


                    if ($user && $user->fcm_token) {
                        $factory = (new Factory)
                            ->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
                
                        $messaging = $factory->createMessaging();
                
                        $notification = Notification::create(
                            'Essential Payment Successful',
                            'Your payment of ₹'.$request->amount.' for the essential was successful. You can now download your payment receipt.'
                        );
                
                        $dataPayload = [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'screen' => 'EssentialsList',
                            'params' => json_encode(['essentialsID' => $essential_payment->essential_id]),
                            'categoryId' => '',
                            'channelId' => '',
                            'sound' => 'bellnotificationsound.wav',
                            'type' => 'MAINTENANCE_PAID',
                            'user_id' => (string)$user->id,
                        ];
                
                        $message = CloudMessage::withTarget('token', $user->fcm_token)
                            ->withNotification($notification)
                            ->withData($dataPayload);
                
                        try {
                            $messaging->send($message);
                        } catch (MessagingException $e) {
                            \Log::error("FCM Error for user {$user->id}: " . $e->getMessage());
                            // Optionally continue notifying others instead of returning early
                        }
                    }
        
    
        return redirect()->back()->with('success', $msg);
    }

}
