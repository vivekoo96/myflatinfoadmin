<?php

namespace App\Listeners;

use App\Event\TakeBookingStoped;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ActiveRecord;
class StopActiveRecord
{

    public function __construct()
    {
        //
    }

    public function handle(TakeBookingStoped $event)
    {
        $vehicle = $event->vehicle;
        $current_active_record = $vehicle->current_active_record;
        if($current_active_record){
            $current_active_record->to = now();
            $diff = abs(strtotime(now()) - strtotime($current_active_record->from));
            $current_active_record->active_time = $diff / 60;
            $current_active_record->amount = ($vehicle->vmodel->vendor_hourly_rate / 60) * ($diff / 60);
            $current_active_record->status = 'Ended';
            $current_active_record->save();
        }
    }
}
