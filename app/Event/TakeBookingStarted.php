<?php

namespace App\Event;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TakeBookingStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $vehicle;
    
    public function __construct($vehicle)
    {
        $this->vehicle = $vehicle;
    }
    
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
