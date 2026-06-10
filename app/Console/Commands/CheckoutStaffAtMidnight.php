<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckoutStaffAtMidnight extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'staff:checkout-midnight';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically checks out staff members who forgot to check out by midnight';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        $today = date('Y-m-d');
        // Find all check-ins for today that do not have a checkout time
        $records = \App\Models\StaffFlatAttendance::where('date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->get();

        foreach ($records as $record) {
            // Check out at 23:59:59 of today
            $checkoutTime = $today . ' 23:59:59';
            $record->update(['check_out_time' => $checkoutTime]);
        }

        $this->info("Checked out " . $records->count() . " staff members.");
        return 0;
    }
}
