<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        Commands\BookingFailed::class,
        Commands\OfflineBookingFailed::class,
        Commands\BookingCompleted::class,
        Commands\FacilityClosed::class,
        Commands\VisitorExpired::class,
        Commands\VisitorReminder::class,
        Commands\VisitorSecurityReminder::class,
        Commands\SendEventStartNotifications::class,
        Commands\SendNoticeFromNotifications::class,
        Commands\DiagnoseNoticeboards::class,
        Commands\SendScheduledNoticeboardNotifications::class,
        Commands\CloseExpiredPolls::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('past:date')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('offline_booking:failed')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('booking:failed')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('booking:completed')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('facility:closed')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('visitor:expired')->everyMinute()->evenInMaintenanceMode();
        // $schedule->command('visitor:reminder')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('visitor:security-reminder')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('events:send-start-notifications')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('notice:send-from-notifications')->everyMinute()->evenInMaintenanceMode();
        // $schedule->command('noticeboard:send-scheduled')->everyMinute()->evenInMaintenanceMode();
         $schedule->command('classified:send-scheduled')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('polls:close-expired')->everyMinute()->evenInMaintenanceMode();
    }

//everyFiveMinutes()
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
        
    }
}
