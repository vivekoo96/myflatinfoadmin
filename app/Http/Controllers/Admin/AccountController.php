<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Transaction;
use App\Models\Event;
use App\Models\Essential;
use App\Models\MaintenancePayment;
use App\Models\Flat;
use App\Models\Payment;
use App\Models\EssentialPayment;

use \Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use App\Helpers\NotificationHelper2 as NotificationHelper;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use App\Services\FCMService;
use App\Models\Notification as DatabaseNotification;

class AccountController extends Controller
{
    public function update_opening_balance(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.statements') )
        {
             if(!Auth::User()->building->hasPermission('Corpusfund')){
                  return redirect('permission-denied')->with('error','Permission denied!');
            }
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        
        $building = Auth::User()->building;
        $transaction = Transaction::where('building_id',$building->id)->where('model','Opening')->first();
        if($transaction){
            return redirect()->back()->with('error','Opening balance already updated');
        }
        $building->maintenance_in_bank = $request->maintenance_in_bank;
        $building->maintenance_in_hand = $request->maintenance_in_hand;
        $building->corpus_in_bank = $request->corpus_in_bank;
        $building->corpus_in_hand = $request->corpus_in_hand;
        $building->save();
        
        $transaction = new Transaction();
        $transaction->building_id = $building->id;
        $transaction->user_id = $building->user_id;
        $transaction->model = 'Maintenance';
        // $transaction->model = 'Opening';
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
        $transaction->model = 'Maintenance';
        $transaction->type = 'Credit';
        $transaction->payment_type = 'InHand';
        $transaction->amount = $request->maintenance_in_hand;
        $transaction->desc = 'Opening Maintenance Balance InCash';
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
        $transaction->desc = 'Opening Corpus Balance InCash';
        $transaction->reciept_no = 'RCP'.rand(10000000,99999999);
        $transaction->status = 'Success';
        $transaction->date = now()->toDateString();
        $transaction->save();
        
        return redirect()->back()->with('success','Opening Balance updated');
    }
    
    public function opening_balance()
    {
      
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.statements') )
        {
           if(!Auth::User()->building->hasPermission('Corpusfund')){
                  return redirect('permission-denied')->with('error','Permission denied!');
            }
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $can_save = 'Yes';
        $building = Auth::User()->building;
        $transaction = Transaction::where('building_id',$building->id)->where('model','Opening')->first();
        if($transaction){
            $can_save = 'No';
        }
        return view('admin.account.opening_balance',compact('building','can_save')); 
    }
    public function income_and_expenditure(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.statements'))
        {
           if(!Auth::User()->building->hasPermission('Corpusfund')){
                  return redirect('permission-denied')->with('error','Permission denied!');
            }
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $building = Auth::User()->building;
        // Only successful transactions belong in the statement; failed/pending
        // gateway attempts must never show up as income/expenditure.
        $transactionsQuery = Transaction::where('building_id', $building->id)
            ->where('status', 'Success');

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
        $building = Auth::User()->building;
        return view('admin.account.statement.income_and_expenditure',compact('building','transactions','inhand','inbank','total_debit','total_credit'));
    }

    public function payment()
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.forms') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $user = Auth::User();
        $building = $user->building;
        $expenses = $building->expenses()->where('type','Debit')->orderByDesc('created_at')->orderByDesc('date')->get();
        return view('admin.account.forms.payment',compact('expenses'));
    }

    public function reciept()
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.forms'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $user = Auth::User();
        $building = $user->building;
        $expenses = $building->expenses()->where('type','Credit')->orderBy('created_at', 'desc')
    ->get();
        return view('admin.account.forms.reciept',compact('expenses'));
    }

    public function manage_maintenance(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.maintenances'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $user = Auth::user();
        $building = $user->building;
        $blocks = $building->blocks;
        $flat_id = $request->flat_id;
    
        $query = MaintenancePayment::where('building_id', $building->id)
            ->whereIn('id', function ($subquery) use ($building) {
                $subquery->selectRaw('MAX(id)')
                    ->from('maintenance_payments')
                    ->where('building_id', $building->id)
                    ->where('status', 'Unpaid')
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
    
        $unpaid = $query->orderBy('created_at', 'desc')->get();
        
        $query = MaintenancePayment::where('building_id', $building->id)
            ->whereIn('id', function ($subquery) use ($building) {
                $subquery->selectRaw('MAX(id)')
                    ->from('maintenance_payments')
                    ->where('building_id', $building->id)
                    ->where('status', 'Paid')
                    ->groupBy('transaction_id');
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
    
        $paid = $query->orderBy('created_at', 'desc')->get();
        $maintenance_payments = $unpaid->merge($paid)->sortByDesc('created_at');
    
        return view('admin.account.maintenance.manage_maintenance', compact('maintenance_payments', 'blocks', 'flat_id'));
    }

    
    public function pay_maintenance($flat_id, Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.maintenances'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $flat = Flat::where('id',$flat_id)->where('building_id',Auth::User()->building_id)->first();
        
        if(!$flat){
            return redirect()->back()->with('error','Flat not found');
        }
        
        // Handle payment_person parameter
        $payment_person = $request->get('payment_person');
        if($payment_person == 'owner' && $flat->owner){
            $user = $flat->owner;
        }elseif($payment_person == 'tenant' && $flat->tanent){
            $user = $flat->tanent;
        }else{
            // Default behavior if no payment_person specified
            if($flat->tanent){
                $user = $flat->tanent;
            }else{
                $user = $flat->owner;
            }
        }
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
        $transactions = Transaction::where('user_id',$user->id)->where('model','Maintenance')->with(['user','maintenance_payments.maintenance'])->orderBy('id','desc')->get();
        
        return view('admin.account.maintenance.pay_maintenance',compact('flat','maintenance_payments','transactions','total_payment','gst','grand_total','last_paid_date','user'));
    }
    
    public function pay_maintenance_bill(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.maintenances'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $flat = Flat::where('id',$request->flat_id)->where('building_id',Auth::User()->building_id)->first();
        
        if(!$flat){
            return redirect()->back()->with('error','Flat not found');
        }
        if($flat->tanent){
            $user = $flat->tanent;
        }else{
            $user = $flat->owner;
        }
        
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
        // $paid_maintenance_payments = MaintenancePayment::where('flat_id', $flat->id)
        //     ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building','transaction'])
        //     ->where('status', 'Paid')
        //     ->orderBy('id', 'desc')
        //     ->get();
        $transactions = Transaction::where('user_id',$user->id)->where('model','Maintenance')->with(['user','maintenance_payments.maintenance'])->orderBy('id','desc')->get();
        // Compare against the single source of truth (flatOutstandingTotal) so this
        // check always agrees with the amount shown on pending-bills / the pay page.
        // Previously it compared an inline re-computation that could drift, making
        // "Pay now" appear broken.
        $expected_total = MaintenancePayment::flatOutstandingTotal($flat->id);
        if(ceil($request->amount) != $expected_total){
            return redirect()->back()->with('error','Paying amount doesnt match to current bill. The current amount due is ₹'.number_format($expected_total, 2).'. Please refresh and try again.');
        }
          \DB::transaction(function () use ($flat, $user, $request, $gst) {
            $transaction = new Transaction();
            $transaction->building_id = Auth::User()->building_id;
            $transaction->user_id = $user->id;
            $transaction->order_id = '';
            $transaction->model = 'Maintenance';
            $transaction->payerrole_id=Auth::User()->id;
            $transaction->flat_id = $flat->id;
            $transaction->block_id = $flat->block->id;
            
            $paid_maintenance = MaintenancePayment::where('flat_id',$flat->id)->orderBy('id','desc')->first();
            $transaction->model_id = $paid_maintenance->maintenance_id;
            $transaction->type = 'Credit';
            $transaction->payment_type = $request->payment_type;
            $transaction->amount = $request->amount;
            $transaction->gst_amount = $gst;
            $transaction->reciept_no = rand();
            $transaction->desc = 'Maintenance Payment paid by flat number '.$flat->name .' through BA: '.Auth::User()->name;
            $transaction->status = 'Success';
            $transaction->date = now()->toDateString();
            $transaction->save();
            
            $maintenance_payment = $paid_maintenance;
            $maintenance_payment->user_id = $user->id;
            $maintenance_payment->paid_amount = $maintenance_payment->dues_amount;
            $maintenance_payment->paid_date = now()->toDateString();
            $maintenance_payment->dues_amount = 0;
            $maintenance_payment->type = 'Credit';
            $maintenance_payment->payment_type = $request->payment_type;
            $maintenance_payment->desc = 'Maintenance Payment paid by flat number '.$flat->name .'  through BA : '.Auth::User()->name;
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
                $maintenance_payment->payment_type = $request->payment_type;
                $maintenance_payment->desc = 'Paid Through BA';
                $maintenance_payment->transaction_id = $transaction->id;
                $maintenance_payment->status = 'Paid';
                $maintenance_payment->save();
            }
          });

        // Check if user has a device token
                $tanent = $flat->tanent;
                $owner = $flat->owner;
                
                $usersToNotify = collect([$tanent, $owner])->filter();

                // foreach ($usersToNotify as $targetUser) {
                //     if ($targetUser && $targetUser->fcm_token) {
                //         $factory = (new Factory)
                //             ->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
                
                //         $messaging = $factory->createMessaging();
                
                //         $notification = Notification::create(
                //             'Maintenance Payment Successful',
                //             'A new maintenance payment has been paid by the BA. Please check the details.'
                //         );
                
                //         $dataPayload = [
                //             'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                //             'screen' => 'MaintenancePage',
                //             'params' => json_encode(['maintenanceId' => $maintenance->id]),
                //             'categoryId' => '',
                //             'channelId' => '',
                //             'sound' => 'bellnotificationsound.wav',
                //             'type' => 'MAINTENANCE_PAID',
                //             'user_id' => (string)$targetUser->id,
                //         ];
                
                //         $message = CloudMessage::withTarget('token', $targetUser->fcm_token)
                //             ->withNotification($notification)
                //             ->withData($dataPayload);
                
                //         try {
                //             $messaging->send($message);
                //         } catch (MessagingException $e) {
                //             \Log::error("FCM Error for user {$targetUser->id}: " . $e->getMessage());
                //             // Optionally continue notifying others instead of returning early
                //         }
                //     }
                // }
                
            $title = 'Maintenance Payment Successful';
            $body = 'A new maintenance payment has been paid by the BA. Please check the details.';
                
            
    // Send notification using helper
    
           foreach ($usersToNotify as $targetUser) {
                    if ($targetUser) {
                                    $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'MaintenancePage',
                'params' => json_encode([
                    
                'maintenanceId' => $maintenance->id,
                'flat_id' => $flat->id,
                'block_id' => $flat->block_id,
                'building_id' =>$flat->building_id,
                 'user_id' =>$targetUser->id,
                ]),
                'categoryId' => 'GatePassUpdate',
                'channelId' => 'GatePass',
                'sound' => 'bellnotificationsound.wav',
                'type' => 'MAINTENANCE_PAID',
            ];
            
    $notificationResult = NotificationHelper::sendNotification(
        $targetUser->id,
        $title,
        $body,
        $dataPayload,
        [
                'from_id' =>  $targetUser->id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => $dataPayload['sound']
            ],['user']
    );
                    }
               
           }
                
        return redirect('account/maintenance/manage')->with('success','Payment recieved successfully');
            
    }
    
    public function maintenance_invoice($maintenance_payment_id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.maintenances'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $maintenance_payment = MaintenancePayment::where('id',$maintenance_payment_id)->where('building_id',Auth::User()->building_id)->first();

        if(!$maintenance_payment){
            return redirect()->back()->with('error','Maintenance payment not found');
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
        
        // return view('partials.reciept.maintenance',compact('flat','maintenance_payments','transactions','total_payment','gst','grand_total','last_paid_date','user'));
        return view('admin.account.maintenance.invoice_maintenance',compact('flat','maintenance_payments','transactions','total_payment','gst','grand_total','last_paid_date','user'));
    }
    
    public function maintenance_reciept($maintenance_payment_id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.maintenances') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $maintenance_payment = MaintenancePayment::where('id',$maintenance_payment_id)->where('building_id',Auth::User()->building_id)->first();

        if(!$maintenance_payment){
            return redirect()->back()->with('error','Maintenance payment not found');
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
        
        return view('admin.account.maintenance.reciept_maintenance',compact('flat','maintenance_payments','transactions','total_payment','gst','grand_total','last_paid_date','user'));
        // return view('partials.reciept.maintenance',compact('flat','maintenance_payments','transactions','total_payment','gst','grand_total','last_paid_date','user'));
    }
    
    public function event_reciept($payment_id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.events') )
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $payment = Payment::where('id',$payment_id)->where('building_id',Auth::User()->building_id)->first();

        if(!$payment){
            return redirect()->back()->with('error','Event payment not found');
        }
        return view('admin.account.reciept.event_reciept',compact('payment'));
    }
    
    public function pay_essential($payment_id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.essentials'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $essential_payment = EssentialPayment::where('id',$payment_id)->where('building_id',Auth::User()->building_id)->first();
        
        if(!$essential_payment){
            return redirect()->back()->with('error','Essential payment not found');
        }
        $flat = $essential_payment->flat;
        if($flat->tanent){
            $user = $flat->tanent;
        }else{
            $user = $flat->owner;
        }

        $dues_amount = $essential_payment->dues_amount;
        $late_fine = 0;
        $gst = $essential_payment->essential->gst;
        
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
        $payment = $essential_payment;
        $total_amount = $dues_amount + $late_fine;
        $total_gst = $total_amount * $gst / 100;
        $grand_total = $total_amount + $total_gst;
        return view('admin.account.invoice.essential_invoice',compact('flat','user','payment','late_fine','total_gst','grand_total'));
    }
    
    public function essential_invoice($payment_id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.essentials'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $essential_payment = EssentialPayment::where('id',$payment_id)->where('building_id',Auth::User()->building_id)->first();
        
        if(!$essential_payment){
            return redirect()->back()->with('error','Essential payment not found');
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
                    $late_fine = ($dues_amount * $essential_payment->essential->late_fine_value) / 100;
                    break;
                }
        }
        $payment = $essential_payment;
        $total_amount = $dues_amount + $late_fine;
        $total_gst = $total_amount * $gst / 100;
        $grand_total = $total_amount + $total_gst;
        return view('admin.account.invoice.essential_invoice',compact('flat','user','payment','late_fine','total_gst','grand_total'));
    }
    
    public function essential_reciept($payment_id)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.essentials'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        $essential_payment = EssentialPayment::where('id',$payment_id)->where('building_id',Auth::User()->building_id)->first();
        
        if(!$essential_payment){
            return redirect()->back()->with('error','Essential payment not found');
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
                    $late_fine = ($dues_amount * $essential_payment->essential->late_fine_value) / 100;
                    break;
                }
        }
        $payment = $essential_payment;
        $total_amount = $dues_amount + $late_fine;
        $total_gst = $total_amount * $gst / 100;
        $grand_total = $total_amount + $total_gst;
        return view('admin.account.reciept.essential_reciept',compact('flat','user','payment','late_fine','total_gst','grand_total'));
    }

 public function pending_bills(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.maintenances') || Auth::User()->hasPermission('custom.essentials'))
        {
            //
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }
        
        $user = Auth::user();
        $building = $user->building;
        $blocks = $building->blocks;
        $flat_id = $request->flat_id;
        $bill_type = $request->bill_type ?? 'all';
        
        // Get pending maintenance payments - grouped by flat_id
        $maintenanceQuery = MaintenancePayment::where('building_id', $building->id)
            ->where('status', 'Unpaid')
            ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
            ->whereHas('maintenance')
            ->whereIn('id', function ($subquery) use ($building) {
                $subquery->selectRaw('MAX(id)')
                    ->from('maintenance_payments')
                    ->where('building_id', $building->id)
                    ->where('status', 'Unpaid')
                    ->groupBy('flat_id');
            });
            
        // Get pending essential payments - grouped by flat_id
        $essentialQuery = EssentialPayment::where('building_id', $building->id)
            ->where('status', 'Unpaid')
            ->with(['essential', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
            ->whereHas('essential')
            ->whereIn('id', function ($subquery) use ($building) {
                $subquery->selectRaw('MAX(id)')
                    ->from('essential_payments')
                    ->where('building_id', $building->id)
                    ->where('status', 'Unpaid')
                    ->groupBy('flat_id');
            });
        
        // Filter by flat_id if provided
        if ($request->filled('flat_id') && $request->flat_id > 0) {
            $maintenanceQuery->where('flat_id', $request->flat_id);
            $essentialQuery->where('flat_id', $request->flat_id);
        }
        
        // Filter by bill type
        if ($bill_type === 'maintenance') {
            $maintenance_payments = $maintenanceQuery->orderBy('created_at', 'desc')->get();
            $essential_payments = collect();
        } elseif ($bill_type === 'essential') {
            $maintenance_payments = collect();
            $essential_payments = $essentialQuery->orderBy('created_at', 'desc')->get();
        } else {
            $maintenance_payments = $maintenanceQuery->orderBy('created_at', 'desc')->get();
            $essential_payments = $essentialQuery->orderBy('created_at', 'desc')->get();
        }
        
        // Each maintenance row is grouped to one-per-flat (latest unpaid bill).
        // Populate the row from the full-flat outstanding breakdown so the
        // Amount/Arrears/Late Fine/GST/Total columns reflect ALL unpaid months
        // and agree with flatOutstandingTotal() (single source of truth).
        foreach ($maintenance_payments as $payment) {
            $breakdown = MaintenancePayment::flatOutstandingBreakdown($payment->flat_id);
            $payment->current_dues = $breakdown['current_dues'];
            $payment->arrears      = $breakdown['arrears'];
            $payment->dues_total   = $breakdown['dues_total'];
            $payment->late_fine    = $breakdown['late_fine'];
            $payment->gst_amount   = $breakdown['gst'];
            $payment->grand_total  = $breakdown['total'];
        }
        
        // Calculate late fees for essential payments
        foreach ($essential_payments as $payment) {
            $essential = $payment->essential;
            $late_fine = 0;
            $gst_rate = 0;
            
            if ($essential) {
                $gst_rate = $essential->gst ?? 0;
                $dueDate = Carbon::parse($essential->due_date);
                if ($dueDate->lt(now()->startOfDay())) {
                    $late_days = $dueDate->diffInDays(now());
                    
                    switch ($essential->late_fine_type) {
                        case 'Daily':
                            $late_fine = $late_days * $essential->late_fine_value;
                            break;
                        case 'Fixed':
                            $late_fine = $essential->late_fine_value;
                            break;
                        case 'Percentage':
                            $late_fine = ($payment->dues_amount * $essential->late_fine_value) / 100;
                            break;
                    }
                }
            }
            
            $payment->late_fine = $late_fine;
            $payment->total_amount = $payment->dues_amount + $late_fine;
            $payment->gst_amount = $payment->total_amount * $gst_rate / 100;
            $payment->grand_total = $payment->total_amount + $payment->gst_amount;
        }
        
        return view('admin.account.pending_bills', compact('maintenance_payments', 'essential_payments', 'blocks', 'flat_id', 'bill_type'));
    }

    public function upi_pending(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts'))
        {
            // allowed
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        $user = Auth::user();
        $building = $user->building;

        $pendingUpi = \App\Models\MaintenancePayment::with(['flat', 'user', 'maintenance', 'flat.owner', 'flat.tanent'])
            ->where('building_id', $building->id)
            ->where('type', 'UPI')
            ->where('upi_payment_status', 'Pending')
            ->orderBy('upi_submitted_at', 'asc')
            ->get();

        return view('admin.account.upi_pending', compact('pendingUpi'));
    }

    // public function pending_bills(Request $request)
    // {
    //     if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts') || Auth::User()->hasPermission('custom.maintenances') || Auth::User()->hasPermission('custom.essentials'))
    //     {
    //         //
    //     }else{
    //         return redirect('permission-denied')->with('error','Permission denied!');
    //     }
        
    //     $user = Auth::user();
    //     $building = $user->building;
    //     $blocks = $building->blocks;
    //     $flat_id = $request->flat_id;
    //     $bill_type = $request->bill_type ?? 'all';
        
    //     // Get pending maintenance payments
    //     $maintenanceQuery = MaintenancePayment::where('building_id', $building->id)
    //         ->where('status', 'Unpaid')
    //         ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
    //         ->whereHas('maintenance'); // Only include payments with valid maintenance
            
    //     // Get pending essential payments
    //     $essentialQuery = EssentialPayment::where('building_id', $building->id)
    //         ->where('status', 'Unpaid')
    //         ->with(['essential', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
    //         ->whereHas('essential'); // Only include payments with valid essential
        
    //     // Filter by flat_id if provided
    //     if ($request->filled('flat_id') && $request->flat_id > 0) {
    //         $maintenanceQuery->where('flat_id', $request->flat_id);
    //         $essentialQuery->where('flat_id', $request->flat_id);
    //     }
        
    //     // Filter by bill type
    //     if ($bill_type === 'maintenance') {
    //         $maintenance_payments = $maintenanceQuery->orderBy('created_at', 'desc')->get();
    //         $essential_payments = collect();
    //     } elseif ($bill_type === 'essential') {
    //         $maintenance_payments = collect();
    //         $essential_payments = $essentialQuery->orderBy('created_at', 'desc')->get();
    //     } else {
    //         $maintenance_payments = $maintenanceQuery->orderBy('created_at', 'desc')->get();
    //         $essential_payments = $essentialQuery->orderBy('created_at', 'desc')->get();
    //     }
        
    //     // Calculate late fees for maintenance payments
    //     foreach ($maintenance_payments as $payment) {
    //         $maintenance = $payment->maintenance;
    //         $late_fine = 0;
    //         $gst_rate = 0;
            
    //         if ($maintenance) {
    //             $gst_rate = $maintenance->gst ?? 0;
    //             $dueDate = Carbon::parse($maintenance->due_date);
    //             if ($dueDate->lt(now()->startOfDay())) {
    //                 $late_days = $dueDate->diffInDays(now());
                    
    //                 switch ($maintenance->late_fine_type) {
    //                     case 'Daily':
    //                         $late_fine = $late_days * $maintenance->late_fine_value;
    //                         break;
    //                     case 'Fixed':
    //                         $late_fine = $maintenance->late_fine_value;
    //                         break;
    //                     case 'Percentage':
    //                         $late_fine = ($payment->dues_amount * $maintenance->late_fine_value) / 100;
    //                         break;
    //                 }
    //             }
    //         }
            
    //         $payment->late_fine = $late_fine;
    //         $payment->total_amount = $payment->dues_amount + $late_fine;
    //         $payment->gst_amount = $payment->total_amount * $gst_rate / 100;
    //         $payment->grand_total = $payment->total_amount + $payment->gst_amount;
    //     }
        
    //     // Calculate late fees for essential payments
    //     foreach ($essential_payments as $payment) {
    //         $essential = $payment->essential;
    //         $late_fine = 0;
    //         $gst_rate = 0;
            
    //         if ($essential) {
    //             $gst_rate = $essential->gst ?? 0;
    //             $dueDate = Carbon::parse($essential->due_date);
    //             if ($dueDate->lt(now()->startOfDay())) {
    //                 $late_days = $dueDate->diffInDays(now());
                    
    //                 switch ($essential->late_fine_type) {
    //                     case 'Daily':
    //                         $late_fine = $late_days * $essential->late_fine_value;
    //                         break;
    //                     case 'Fixed':
    //                         $late_fine = $essential->late_fine_value;
    //                         break;
    //                     case 'Percentage':
    //                         $late_fine = ($payment->dues_amount * $essential->late_fine_value) / 100;
    //                         break;
    //                 }
    //             }
    //         }
            
    //         $payment->late_fine = $late_fine;
    //         $payment->total_amount = $payment->dues_amount + $late_fine;
    //         $payment->gst_amount = $payment->total_amount * $gst_rate / 100;
    //         $payment->grand_total = $payment->total_amount + $payment->gst_amount;
    //     }
        
    //     return view('admin.account.pending_bills', compact('maintenance_payments', 'essential_payments', 'blocks', 'flat_id', 'bill_type'));
    // }
    
//     public function send_due_notifications(Request $request)
//     {
//     if (!(
//         Auth::user()->role == 'BA' ||
//         Auth::user()->hasRole('president') ||
//         Auth::user()->hasRole('accounts') ||
//         Auth::user()->hasPermission('custom.maintenances') ||
//         Auth::user()->hasPermission('custom.essentials')
//     )) {
//         return redirect('permission-denied')->with('error', 'Permission denied!');
//     }

//     $building = Auth::user()->building;
//     $notification_type = $request->notification_type;
//     $flat_ids = $request->flat_ids;

//     if (is_string($flat_ids)) {
//         $flat_ids = json_decode($flat_ids, true);
//     }
//     if (!is_array($flat_ids)) {
//         $flat_ids = [];
//     }

//     $fcm = new FCMService();
//     $sent_count = 0;
//     $errors = [];

//     // Maintenance dues
//     if ($notification_type === 'all' || $notification_type === 'maintenance') {
//         $tokens = [];

//         $mQuery = \App\Models\MaintenancePayment::where('building_id', $building->id)
//             ->where('status', 'Unpaid')
//             ->with(['flat.owner', 'flat.tanent']);

//         if (!empty($flat_ids)) $mQuery->whereIn('flat_id', $flat_ids);

//         foreach ($mQuery->get() as $mp) {
//             if ($mp->flat && $mp->flat->tanent) {
//                 $tokens = array_merge($tokens, $this->getUserTokens($mp->flat->tanent->id));
//             }
//             if ($mp->flat && $mp->flat->owner) {
//                 $tokens = array_merge($tokens, $this->getUserTokens($mp->flat->owner->id));
//             }
//         }

//         $result = $fcm->sendToMultipleDevices(
//             $tokens,
//             'Maintenance Bill Due',
//             'Your maintenance bill may be due or overdue. Please check and pay.',
//             ['screen' => 'MaintenancePage', 'type' => 'MAINTENANCE_DUE']
//         );

//         $sent_count += $result['success'];
//         $errors = array_merge($errors, $result['results']);
//     }

//     // Essential dues
//     if ($notification_type === 'all' || $notification_type === 'essential') {
//         $tokens = [];

//         $eQuery = \App\Models\EssentialPayment::where('building_id', $building->id)
//             ->where('status', 'Unpaid')
//             ->with(['flat.owner', 'flat.tanent']);

//         if (!empty($flat_ids)) $eQuery->whereIn('flat_id', $flat_ids);

//         foreach ($eQuery->get() as $ep) {
//             if ($ep->flat && $ep->flat->tanent) {
//                 $tokens = array_merge($tokens, $this->getUserTokens($ep->flat->tanent->id));
//             }
//             if ($ep->flat && $ep->flat->owner) {
//                 $tokens = array_merge($tokens, $this->getUserTokens($ep->flat->owner->id));
//             }
//         }

//         $result = $fcm->sendToMultipleDevices(
//             $tokens,
//             'Essential Bill Due',
//             'Your essential contribution may be due or overdue. Please check and pay.',
//             ['screen' => 'EssentialPage', 'type' => 'ESSENTIAL_DUE']
//         );

//         $sent_count += $result['success'];
//         $errors = array_merge($errors, $result['results']);
//     }

//     $message = "Successfully sent {$sent_count} notifications.";
//     if (count($errors) > 0) $message .= ' Some sends failed.';

//     return redirect()->back()->with('success', $message);
// }

  public function send_due_notifications(Request $request)
    {
    if (!(
        Auth::user()->role == 'BA' ||
        Auth::user()->hasRole('president') ||
        Auth::user()->hasRole('accounts') ||
        Auth::user()->hasPermission('custom.maintenances') ||
        Auth::user()->hasPermission('custom.essentials')
    )) {
        return redirect('permission-denied')->with('error', 'Permission denied!');
    }

    $building = Auth::user()->building;
    $notification_type = $request->notification_type;
    $flat_ids = $request->flat_ids;

    if (is_string($flat_ids)) {
        $flat_ids = json_decode($flat_ids, true);
    }
    if (!is_array($flat_ids)) {
        $flat_ids = [];
    }

    $sent_count = 0;
    $skipped_count = 0;

    // Don't re-remind the same flat for the same bill type within this window.
    $cooldownHours = 24;
    $cooldownSince = now()->subHours($cooldownHours);

    // Maintenance dues
    if ($notification_type === 'all' || $notification_type === 'maintenance') {
        $title = 'Maintenance Bill Due';
        $body = 'Your maintenance bill may be due or overdue. Please check and pay.';

        $mQuery = \App\Models\MaintenancePayment::where('building_id', $building->id)
            ->where('status', 'Unpaid')
            ->with(['flat.owner', 'flat.tanent']);

        if (!empty($flat_ids)) $mQuery->whereIn('flat_id', $flat_ids);

        // One reminder per flat (a flat may have several unpaid months).
        $flatsToNotify = $mQuery->get()->filter(fn($mp) => $mp->flat)->keyBy('flat_id');

        foreach ($flatsToNotify as $flatId => $mp) {
            // Cooldown guard: skip flats reminded for maintenance within the window.
            $recentlySent = \App\Models\BillReminderLog::where('building_id', $building->id)
                ->where('flat_id', $flatId)
                ->where('bill_type', 'maintenance')
                ->where('created_at', '>=', $cooldownSince)
                ->exists();
            if ($recentlySent) { $skipped_count++; continue; }

            $recipients = 0;
            foreach ([$mp->flat->tanent, $mp->flat->owner] as $recipient) {
                if (!$recipient) continue;

                // Same delivery path as the (working) maintenance-added alerts:
                // NotificationHelper (=> NotificationHelper2) saves the in-app
                // notification AND pushes via FCM/APNs, so iOS receives it too.
                $dataPayload = [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'screen'       => 'MaintenancePage',
                    'params'       => json_encode([
                        'flat_id'     => $flatId,
                        'building_id' => $building->id,
                        'user_id'     => $recipient->id,
                    ]),
                    'categoryId'   => '',
                    'channelId'    => '',
                    'sound'        => 'bellnotificationsound.wav',
                    'type'         => 'MAINTENANCE_DUE',
                ];

                \App\Helpers\NotificationHelper2::sendNotification(
                    $recipient->id,
                    $title,
                    $body,
                    $dataPayload,
                    [
                        'from_id'     => Auth::user()->id,
                        'flat_id'     => $flatId,
                        'building_id' => $building->id,
                        'type'        => 'maintenance_due',
                        'apns_client' => $this->apnsClient ?? null,
                        'ios_sound'   => $dataPayload['sound'],
                    ],
                    ['user']
                );
                $recipients++;
            }

            if ($recipients > 0) $sent_count += $recipients;

            \App\Models\BillReminderLog::create([
                'building_id'      => $building->id,
                'flat_id'          => $flatId,
                'bill_type'        => 'maintenance',
                'sent_by'          => Auth::user()->id,
                'recipients_count' => $recipients,
            ]);
        }
    }

    // Essential dues
    if ($notification_type === 'all' || $notification_type === 'essential') {
        $title = 'Essential Bill Due';
        $body = 'Your essential contribution may be due or overdue. Please check and pay.';

        $eQuery = \App\Models\EssentialPayment::where('building_id', $building->id)
            ->where('status', 'Unpaid')
            ->with(['flat.owner', 'flat.tanent']);

        if (!empty($flat_ids)) $eQuery->whereIn('flat_id', $flat_ids);

        $flatsToNotify = $eQuery->get()->filter(fn($ep) => $ep->flat)->keyBy('flat_id');

        foreach ($flatsToNotify as $flatId => $ep) {
            // Cooldown guard: skip flats reminded for essential within the window.
            $recentlySent = \App\Models\BillReminderLog::where('building_id', $building->id)
                ->where('flat_id', $flatId)
                ->where('bill_type', 'essential')
                ->where('created_at', '>=', $cooldownSince)
                ->exists();
            if ($recentlySent) { $skipped_count++; continue; }

            $recipients = 0;
            foreach ([$ep->flat->tanent, $ep->flat->owner] as $recipient) {
                if (!$recipient) continue;

                // NotificationHelper2 saves the in-app notification (so the admin
                // has a record of what was posted) AND pushes via FCM/APNs (iOS too).
                $dataPayload = [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'screen'       => 'EssentialPage',
                    'params'       => json_encode([
                        'flat_id'     => $flatId,
                        'building_id' => $building->id,
                        'user_id'     => $recipient->id,
                    ]),
                    'categoryId'   => '',
                    'channelId'    => '',
                    'sound'        => 'bellnotificationsound.wav',
                    'type'         => 'ESSENTIAL_DUE',
                ];

                \App\Helpers\NotificationHelper2::sendNotification(
                    $recipient->id,
                    $title,
                    $body,
                    $dataPayload,
                    [
                        'from_id'     => Auth::user()->id,
                        'flat_id'     => $flatId,
                        'building_id' => $building->id,
                        'type'        => 'essential_due',
                        'apns_client' => $this->apnsClient ?? null,
                        'ios_sound'   => $dataPayload['sound'],
                    ],
                    ['user']
                );
                $recipients++;
            }

            if ($recipients > 0) $sent_count += $recipients;

            \App\Models\BillReminderLog::create([
                'building_id'      => $building->id,
                'flat_id'          => $flatId,
                'bill_type'        => 'essential',
                'sent_by'          => Auth::user()->id,
                'recipients_count' => $recipients,
            ]);
        }
    }

    $message = "Successfully sent {$sent_count} notification(s).";
    if ($skipped_count > 0) {
        $message .= " Skipped {$skipped_count} flat(s) already reminded within the last {$cooldownHours}h.";
    }

    return redirect()->back()->with('success', $message);
}

private function getUserTokens($userId)
{
    return \App\Models\UserDevice::where('user_id', $userId)
        ->whereNotNull('fcm_token')
        ->pluck('fcm_token')
        ->toArray();
}

    /**
     * History of pending-bill reminders sent for this building, so the admin can
     * see who was already reminded (and when) before sending again.
     */
    public function reminder_history(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts'))
        {
            // allowed
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        $building = Auth::user()->building;

        $query = \App\Models\BillReminderLog::with(['flat.block', 'sender'])
            ->where('building_id', $building->id);

        if ($request->filled('flat_id') && $request->flat_id > 0) {
            $query->where('flat_id', $request->flat_id);
        }
        if ($request->filled('bill_type') && in_array($request->bill_type, ['maintenance', 'essential'])) {
            $query->where('bill_type', $request->bill_type);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs   = $query->orderBy('created_at', 'desc')->get();
        $blocks = $building->blocks;

        return view('admin.account.reminder_history', compact('logs', 'blocks'));
    }

    /**
     * History of UPI payment submissions that have been approved or rejected
     * (the live queue lives on account/upi-pending).
     */
    public function upi_history(Request $request)
    {
        if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts'))
        {
            // allowed
        }else{
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        $building = Auth::user()->building;

        $query = \App\Models\MaintenancePayment::with(['flat.block', 'flat.owner', 'flat.tanent', 'user', 'maintenance', 'transaction'])
            ->where('building_id', $building->id)
            ->where('type', 'UPI')
            ->whereIn('upi_payment_status', ['Approved', 'Rejected'])
            // Only settled (Paid) rows belong in history. Rejected submissions are
            // reset to status 'Unpaid' so the resident can resubmit, so they must
            // not appear here.
            ->where('status', 'Paid');

        if ($request->filled('status') && in_array($request->status, ['Approved', 'Rejected'])) {
            $query->where('upi_payment_status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('upi_submitted_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('upi_submitted_at', '<=', $request->to_date);
        }

        $payments = $query->orderBy('updated_at', 'desc')->get();

        return view('admin.account.upi_history', compact('payments'));
    }

}
