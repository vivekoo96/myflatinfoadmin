<?php

namespace App\Listeners;

use App\Event\TakeBookingStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ActiveRecord;

class StartActiveRecord
{

    public function __construct()
    {
        //
    }
    
    public function handle(TakeBookingStarted $event)
    {
        $vehicle = $event->vehicle;
        $current_active_record = new ActiveRecord();
        $current_active_record->user_id = $vehicle->vendor_id;
        $current_active_record->vehicle_id = $vehicle->id;
        $current_active_record->from = now();
        $current_active_record->save();
    }
}
