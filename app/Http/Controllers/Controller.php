<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use \Hash;
use \Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    public function send_notification($token,$title,$body)
    {
        //$token = User::whereNotNull('device_token')->pluck('device_token')->all();
        //$token = User::where('role',$request->to)->whereNotNull('device_token')->pluck('device_token')->all();
        $SERVER_API_KEY = \App\Models\Setting::first()->fcm_key;
        $data = [
            "registration_ids" => $token,
            "notification" => [
                "title" => $title,
                "body" => $body,
            ]
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
    }
    public function send_sms($number,$otp)
    {
        $curl = curl_init();
        $data = array(
            'flow_id' => '60d31aa780770a194e244e18',
            'sender' => 'CEIOTP',
            'mobiles' => '91'.$number,
            'OTP' => $otp
        );
        $payload = json_encode($data);
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.msg91.com/api/v5/flow/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                "authkey: 359397A31iCLUqH9m60824c3bP1",
                "content-type: application/JSON"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
    }

    public function update_profiles($request)
    {
        $user = Auth::User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $gender = $request->gender;
        if($request->gender == 'male'){
            $gender = 'Male';
        }
        if($request->gender == 'female'){
            $gender = 'Female';
        }
        $user->gender = $gender;
        
        $user->address = $request->address;
        if($request->hasFile('photo')) {
            $file= $request->file('photo');
            $allowedfileExtension=['JPEG','jpg','png'];
            $extension = $file->getClientOriginalExtension();
            $check = in_array($extension,$allowedfileExtension);
            if($check){
                $file_path = public_path('/images/profiles/'.$user->photo);
                if(file_exists($file_path) && $user->photo != '')
                {
                    unlink($file_path);
                }
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $filename = substr(str_shuffle(str_repeat($pool, 5)), 0, 12) .'.'.$extension;
                $path = $file->move(public_path('/images/profiles/'), $filename);
                $user->photo = $filename;
            }else{
                return redirect()->back()->withInput($request->input())->with('error','Please upload only JPEG,jpg,png images');
            }
        }
        if($request->password){
            $user->password = Hash::make($request->password);
        }
        $user->save();
    }
}
