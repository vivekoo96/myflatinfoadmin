<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \App\Models\Timing;
use \App\Models\Notification;
use Carbon\Carbon;
class PastDate extends Command
{

    protected $signature = 'past:date';

    protected $description = 'Remove past dates';

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
        $today = Carbon::today()->toDateString();
    
        // Get all timings that have at least one past date
        $timings = Timing::all();
    
        foreach ($timings as $timing) {
            $dates = json_decode($timing->dates, true) ?? [];
    
            // Keep only today or future dates
            $filtered = array_filter($dates, function ($date) use ($today) {
                return $date >= $today;
            });
    
            // If any change happened, save back
            if (count($filtered) !== count($dates)) {
                $timing->dates = json_encode(array_values($filtered));
                $timing->save();
            }
        }
    
        $this->info('Past dates removed from all timings.');
    }

}
