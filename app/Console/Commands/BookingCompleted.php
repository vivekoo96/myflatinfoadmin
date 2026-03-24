<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \App\Models\Booking;

class BookingCompleted extends Command
{

    protected $signature = 'booking:completed';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $bookings = Booking::with('timing')
        ->whereIn('status', ['Success', 'Created'])
        ->get()
        ->filter(function ($booking) {
            $bookingEnd = \Carbon\Carbon::parse($booking->date.' '.$booking->timing->to);
            return $bookingEnd->lt(now());
        });
    
        foreach ($bookings as $booking) {
            $booking->status = $booking->status === 'Success' ? 'Completed' : 'Failed';
            $booking->save();
        }
    }

}
