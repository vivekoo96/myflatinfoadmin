<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Setting;
use App\Models\Booking;
use App\Models\Order;


use Razorpay\Api\Api;
use Razorpay\Api\Utility;
use Razorpay\Api\Errors\SignatureVerificationError;



class WebhookController extends Controller
{
    public function __construct()
    {
        
        $rdata = Setting::findOrFail(1);
        $this->keyId = $rdata->razorpay_key;
        $this->keySecret = $rdata->razorpay_secret;
        $this->displayCurrency = 'INR';
        $this->webhookSecret = 'myflat';
        $this->api = new Api($this->keyId, $this->keySecret);
    }
    
    public function get_setting()
    {
        $setting = Setting::first();
        return response()->json([
            'setting' => $setting
        ],200); 
    }

    public function facility_failed_webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
    
        try {
            $webhookSecret = $this->webhookSecret;
            (new Utility())->verifyWebhookSignature($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationError $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    
        $data = json_decode($payload, true);
    
        if ($data['event'] === 'payment.failed') {
            $razorpayOrderId = $data['payload']['payment']['entity']['order_id'];
    
            $order = Order::where('order_id', $razorpayOrderId)->where('status', 'Created')->first();
    
            if ($order) {
                $order->status = 'Failed';
                $order->save();
    
                $bookings = Booking::where('order_id', $order->id)->get();
                foreach ($bookings as $booking) {
                    $booking->status = 'Failed';
                    $booking->save();
                }
            }
        }
    
        return response()->json(['status' => 'ok'], 200);
    }


    
}