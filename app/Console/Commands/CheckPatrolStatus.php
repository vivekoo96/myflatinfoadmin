<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BuildingShift;
use App\Models\PatrolLocation;
use App\Models\PatrolDailyLog;
use App\Models\GuardPatrolAssignment;
use App\Models\User;
use App\Helpers\NotificationHelper2;
use Carbon\Carbon;

class CheckPatrolStatus extends Command
{
    protected $signature = 'patrol:check-status';
    protected $description = 'Check patrol status and send notifications for not started or not completed patrols';

    const NOT_STARTED_MINUTES = 5;  // Send notification if not started after 5 minutes

    public function handle()
    {
        $this->info('Checking patrol status...');
        $now = Carbon::now();

        // Get all active shifts
        $activeShifts = BuildingShift::where('status', 'Active')
            ->get();

        foreach ($activeShifts as $shift) {
            $this->checkShiftPatrols($shift, $now);
        }

        $this->info('Patrol status check completed');
    }

    private function checkShiftPatrols($shift, $now)
    {
        $today = $now->toDateString();
        $shiftStart = Carbon::parse($today . ' ' . $shift->start_time);
        $shiftEnd = Carbon::parse($today . ' ' . $shift->end_time);

        // Get all patrol schedules for this shift
        $schedules = PatrolLocation::where('building_id', $shift->building_id)
            ->where('building_shift_id', $shift->id)
            ->whereNotNull('gate_id')
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->get();

        foreach ($schedules as $schedule) {
            $gate = $schedule->gate;
            if (!$gate) continue;

            // Get assigned guards for this gate/shift
            $guardAssignments = GuardPatrolAssignment::where('gate_id', $gate->id)
                ->where('building_shift_id', $shift->id)
                ->where('building_id', $shift->building_id)
                ->where('status', 'Active')
                ->whereNull('deleted_at')
                ->pluck('guard_user_id');

            if ($guardAssignments->isEmpty()) continue;

            $guards = User::whereIn('id', $guardAssignments)->get();

            foreach ($guards as $guard) {
                $this->checkGuardPatrol($schedule, $guard, $shift, $shiftStart, $shiftEnd, $today, $now);
            }
        }
    }

    private function checkGuardPatrol($schedule, $guard, $shift, $shiftStart, $shiftEnd, $today, $now)
    {
        // Check if guard has started patrol (any check-in for this schedule today)
        $hasStarted = PatrolDailyLog::where('patrol_schedule_id', $schedule->id)
            ->where('guard_user_id', $guard->id)
            ->where('patrol_date', $today)
            ->exists();

        $minutesSinceShiftStart = $now->diffInMinutes($shiftStart);

        // ✓ CHECK 1: Send notification if patrol NOT STARTED after 5 minutes
        if (!$hasStarted && $minutesSinceShiftStart >= self::NOT_STARTED_MINUTES) {
            $this->sendNotStartedAlert($schedule, $guard, $shift);
        }

        // ✓ CHECK 2: Send notification if patrol NOT COMPLETED at shift end time
        if ($now->greaterThanOrEqualTo($shiftEnd)) {
            $isCompleted = $this->isPatrolCompleted($schedule, $guard, $today);

            if (!$isCompleted) {
                $this->sendNotCompletedAlert($schedule, $guard, $shift);
            }
        }
    }

    private function isPatrolCompleted($schedule, $guard, $today)
    {
        // Get total eligible locations for today
        $totalLocations = PatrolLocation::where('building_id', $schedule->building_id)
            ->whereNull('gate_id')
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '<=', $today)
            ->count();

        if ($totalLocations === 0) return true;  // No locations to check

        // Get completed locations by this guard
        $completedLocations = PatrolDailyLog::where('patrol_schedule_id', $schedule->id)
            ->where('guard_user_id', $guard->id)
            ->where('patrol_date', $today)
            ->count();

        return $completedLocations >= $totalLocations;
    }

    private function sendNotStartedAlert($schedule, $guard, $shift)
    {
        // Check if we already sent this notification today
        if ($this->notificationAlreadySent('not_started', $guard->id, $schedule->id)) {
            return;
        }

        $message = "Patrol not started! You have {$self::NOT_STARTED_MINUTES} minutes to start your patrol for {$schedule->gate->name} - {$shift->name} ({$shift->start_time}).";

        NotificationHelper2::sendNotification(
            $guard->id,
            'Patrol Not Started',
            $message,
            [],
            [
                'save_to_db' => true,
                'building_id' => $schedule->building_id,
                'type' => 'patrol_not_started',
                'schedule_id' => $schedule->id
            ]
        );

        $this->recordNotificationSent('not_started', $guard->id, $schedule->id);
        $this->info("Sent 'not started' notification to guard: {$guard->name}");
    }

    private function sendNotCompletedAlert($schedule, $guard, $shift)
    {
        // Check if we already sent this notification today
        if ($this->notificationAlreadySent('not_completed', $guard->id, $schedule->id)) {
            return;
        }

        $message = "Patrol incomplete! Shift {$shift->name} has ended but you haven't completed all locations for {$schedule->gate->name}. Please complete remaining locations ASAP.";

        NotificationHelper2::sendNotification(
            $guard->id,
            'Patrol Not Completed',
            $message,
            [],
            [
                'save_to_db' => true,
                'building_id' => $schedule->building_id,
                'type' => 'patrol_not_completed',
                'schedule_id' => $schedule->id
            ]
        );

        $this->recordNotificationSent('not_completed', $guard->id, $schedule->id);
        $this->info("Sent 'not completed' notification to guard: {$guard->name}");
    }

    private function notificationAlreadySent($type, $guardId, $scheduleId)
    {
        $key = "patrol_notification_{$type}_{$guardId}_{$scheduleId}_" . now()->toDateString();
        return \Illuminate\Support\Facades\Cache::has($key);
    }

    private function recordNotificationSent($type, $guardId, $scheduleId)
    {
        $key = "patrol_notification_{$type}_{$guardId}_{$scheduleId}_" . now()->toDateString();
        \Illuminate\Support\Facades\Cache::put($key, true, now()->addDay());
    }
}
