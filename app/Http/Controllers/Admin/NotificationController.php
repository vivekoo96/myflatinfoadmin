<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationStep;
use App\Models\User;
use App\Models\Notification;
use App\Models\Setting;

use \Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $setting = Setting::first();
        $notifications = Notification::orderBy('id','asc')->get();
        $recieved_notifications = Notification::where('user_id',Auth::User()->id)->get();
        $sent_notifications = Notification::where('from_id',Auth::User()->id)->get();
        return view('admin.notifications.index',compact('notifications','recieved_notifications','sent_notifications'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //$token = User::whereNotNull('device_token')->pluck('device_token')->all();
        // $token = User::where('role',$request->to)->whereNotNull('device_token')->pluck('device_token')->all();
        $tokens = User::whereNotNull('fcm_token')->pluck('fcm_token')->all();
        $SERVER_API_KEY = \App\Models\Setting::first()->fcm_key;
        $data = [
            "registration_ids" => $tokens,
            "notification" => [
                "title" => "Your Post Was Rejected",
                "body"  => "Unfortunately, your classified was not approved. Please review and edit your post before resubmitting.",
                "sound" => "bellnotificationsound.wav",
            ],
            "data" => [
                "screen"     => "Classifieds",
                "params"     => json_encode([
                    "ScreenTab" => "Post Status",
                    "id"        => "34"
                ]),
                "categoryId" => "UnplannedVisitors",
                "channelId"  => "app",
                "click_action" => "FLUTTER_NOTIFICATION_CLICK"
            ],
            "priority" => "high"
        ];
        $dataString = json_encode($data);
        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        $response = curl_exec($ch);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
    
        return response()->json([
            'http_code' => $httpCode,
            'fcm_response' => json_decode($response, true),
            'curl_error' => $curlError
        ]);
        //$users = User::whereNotNull('device_token')->get();
        // if($request->to == 'vendor'){
        //     $user = User::find(2);
        // }else{
        //     $user = User::find(3);
        // }

        // $notification = new Notification();
        // $notification->user_id = $user->id;
        // $notification->title = $request->title;
        // $notification->body = $request->body;
        // $notification->save();

        return redirect()->back()->with('success','Notification sent');
    }

    public function show($id)
    {
        $notification = Notification::find($id);
        $notification->admin_read = 1;
        $notification->save();
        return view('admin.notifications.show',compact('notification'));
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        
    }
    
    public function mark_all_as_read(Request $request)
    {
        $notifications = Notification::where('admin_read',0)->get();
        foreach($notifications as $notification){
            $notification->admin_read = 1;
            $notification->save();
        }
        return redirect()->back()->with('success','All notification marked as read');
    }
    
}
