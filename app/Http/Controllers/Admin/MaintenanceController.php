<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Maintenance;
use App\Models\MaintenancePayment;
use App\Models\Building;
use App\Models\Flat;
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
use App\Helpers\NotificationHelper2 as NotificationHelper;
use DB;
use \Session;
use Mail;
use \Str;
use \Log;
use Carbon\Carbon;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestException;

class MaintenanceController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            // Use allBuildings() helper for consistent building collection
            $buildings = method_exists($user, 'allBuildings') ? $user->allBuildings() : collect();
            if ($buildings->count() > 0) {
                foreach ($buildings as $building) {
                    if ($building->payment_is_active === 'Yes' && $building->maintenance_is_active === 'Yes') {
                        if ($building->razorpay_key === '' || $building->razorpay_secret === '') {
                            return redirect()->route('setting.index')->with('error', 'Razorpay key and secret is not yet set in settings for one or more buildings, please set that first and try again');
                        }
                    }
                }
            } else if ($user && $user->building && $user->building->payment_is_active === 'Yes' && $user->building->maintenance_is_active === 'Yes') {
                if ($user->building->razorpay_key === '' || $user->building->razorpay_secret === '') {
                    return redirect()->route('setting.index')->with('error', 'Razorpay key and secret is not yet set in settings, please set that first and try again');
                }
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
        // dd("wefwewwwwwwwwfw");
        if (Auth::user()->role !== 'BA' && (!Auth::user()->selectedRole || (Auth::user()->selectedRole->name !== 'Accounts' && Auth::user()->selectedRole->name !== 'President')) && !Auth::User()->hasPermission('custom.maintenances')) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }
        
        $building = Auth::User()->building;
        return view('admin.maintenance.index',compact('building'));
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $rules = [
            // 'building_id' => 'required|exists:buildings,id',
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
    
        $msg = 'Maintenance added Susccessfully';
        $maintenance = new Maintenance();
        $oldStatus = null;

        if($request->id) {
            $maintenance = Maintenance::withTrashed()->find($request->id);
            $msg = 'Maintenance udated Susccessfully';
            $oldStatus = $maintenance->status; // capture previous status to avoid duplicate notifications
        }
    
        $validation = \Validator::make($request->all(), $rules, $messages);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        $user = Auth::User();
        $maintenance->user_id = $user->id;
        $maintenance->building_id = Auth::User()->building_id;
        $maintenance->maintenance_created_by=Auth::User()->id;
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
        
        // Only create maintenance payments if status is Active (not Pending)
        if ($request->status === 'Active') {
            foreach($user->building->flats as $flat)
            {
                if($flat->sold_out == 'Yes'){
                    $maintenance_payment = null;
                    
                    if($request->id) {
                        // When editing, check if payment already exists
                        $maintenance_payment = MaintenancePayment::where('flat_id',$flat->id)
                            ->where('maintenance_id',$maintenance->id)
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
                    $maintenance_payment->bill_no = 'MFIB'.rand(100000,999999);
                    if($maintenance->maintenance_type == 'Areawise'){
                        if($flat->living_status == 'Vacant'){
                            $maintenance_payment->dues_amount = $flat->area * $request->vacant_amount;
                        }else{
                            $maintenance_payment->dues_amount = $flat->area * $request->amount;
                        }
                    }else{
                        if($flat->living_status == 'Vacant'){
                            $maintenance_payment->dues_amount = $request->vacant_amount;
                        }else{
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
        
        $building = Auth::user()->building;

        // Only send notifications when maintenance is newly set to Active
        if ($maintenance->status === 'Active' && $oldStatus !== 'Active') {

           if ($request->id) {
                // If transitioning from Pending to Active, treat as new
                if ($oldStatus === 'Pending') {
                    $title = 'New Maintenance Payment Added';
                    $body = "A new maintenance payment has been added by the BA. Please check the details and complete the payment.";
                } else {
                    $title = 'Maintenance Payment Updated';
                    $body = "An upcoming Maintenance Payment has been updated. Tap to view the latest information.";
                }
            } else {
                $title = 'New Maintenance Payment Added';
                $body = "A new maintenance payment has been added by the BA. Please check the details and complete the payment.";
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

                foreach ($recipients as $user) {
                    $dataPayload = [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'screen' => 'MaintenancePage',
                        'params' => json_encode([
                            'maintenanceId' => $maintenance->id,
                            'flat_id' => $flat->id,
                            'building_id' => $flat->building->id,
                            'user_id' => $user->id,
                        ]),
                        'categoryId' => '',
                        'channelId' => '',
                        'sound' => 'bellnotificationsound.wav',
                        'type' => 'MAINTENANCE_ADDED',
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
                            'type' => 'maintenance_added',
                            'apns_client' => $this->apnsClient ?? null,
                            'ios_sound' => $dataPayload['sound'],
                        ],['user']
                    );
                }
            }
        }

    
    
        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        $user = Auth::User();
        $maintenance = Maintenance::where('id',$id)->where('building_id',$user->building_id)->withTrashed()->first();
        if(!$maintenance){
            return redirect()->route('maintenance.index');
        }
        return view('admin.maintenance.show',compact('maintenance'));
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
        $maintenance = Maintenance::where('id',$id)->withTrashed()->first();
        if($request->action == 'delete'){
            $maintenance->delete();
        }else{
            $maintenance->restore();
        }
        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function update_maintenance_status(Request $request)
    {
        $maintenance = Maintenance::where('id',$request->id)->withTrashed()->first();
        if(!$maintenance){
            return response()->json(['msg' => 'error','error'=>'Maintenance not found'], 404);
        }

        $oldStatus = $maintenance->status;
        $maintenance->status = ($maintenance->status == 'Active') ? 'Inactive' : 'Active';
        $maintenance->save();

        // If status has just changed to Active (i.e. previously not Active), send notifications
        if ($maintenance->status === 'Active' && $oldStatus !== 'Active') {
            $building = $maintenance->building;
            $title = 'Maintenance Payment Active';
            $body = "Maintenance Payment has been activated. Tap to view details.";

            $flats = $building->flats()->where('sold_out', 'Yes')->get();
            foreach ($flats as $flat) {
                $mp = MaintenancePayment::where('maintenance_id', $maintenance->id)
                    ->where('flat_id', $flat->id)
                    ->first();

                // If the payment already exists and is Paid, skip notifying this flat
                if ($mp && $mp->status === 'Paid') {
                    continue;
                }

                $recipients = collect();
                if ($flat->owner) $recipients->push($flat->owner);
                if ($flat->tanent) $recipients->push($flat->tanent);

                foreach ($recipients as $user) {
                    $dataPayload = [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'screen' => 'MaintenancePage',
                        'params' => json_encode([
                            'maintenanceId' => $maintenance->id,
                            'flat_id' => $flat->id,
                            'building_id' => $flat->building->id,
                            'user_id' => $user->id,
                        ]),
                        'categoryId' => '',
                        'channelId' => '',
                        'sound' => 'bellnotificationsound.wav',
                        'type' => 'MAINTENANCE_ADDED',
                    ];

                    NotificationHelper::sendNotification(
                        $user->id,
                        $title,
                        $body,
                        $dataPayload,
                        [
                            'from_id' => $user->id,
                            'flat_id' => $flat->id,
                            'building_id' => $flat->building_id,
                            'type' => 'maintenance_added',
                            'apns_client' => $this->apnsClient ?? null,
                            'ios_sound' => $dataPayload['sound'],
                        ], ['user']
                    );
                }
            }
        }

        return response()->json([
            'msg' => 'success'
        ],200);
    }
    
    public function store_maintenance_payment(Request $request)
    {
        $rules = [
            'flat_id' => 'required|exists:flats,id',
            'maintenance_id' => 'required|exists:maintenances,id',
            'amount' => 'required',
            'type' => 'required|in:Cash,Online,Created',
            'status' => 'required|in:Paid,Unpaid',
        ];
    
        $msg = 'Maintenance payment added Susccessfully';
        $maintenance_payment = new MaintenancePayment();
    
        if ($request->id) {
            $maintenance_payment = MaintenancePayment::withTrashed()->find($request->id);
            $msg = 'Maintenance payment udated Susccessfully';
        }
    
        $validation = \Validator::make($request->all(), $rules);
    
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }
        $user = Auth::User();
        $flat = Flat::find($request->flat_id);
        $maintenance_payment->user_id = $user->id;
        $maintenance_payment->building_id = $flat->building_id;
        $maintenance_payment->flat_id = $flat->id;
        $maintenance_payment->maintenance_id = $request->maintenance_id;
        $maintenance_payment->paid_amount = $request->amount;
        $maintenance_payment->dues_amount = $request->dues_amount - $request->amount;
        $maintenance_payment->late_fine = $request->late_fine;
        $maintenance_payment->status = $request->status;
        $maintenance_payment->save();
    
        return redirect()->back()->with('success', $msg);
    }

}
