<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GuardPatrolAssignment;
use App\Models\BuildingShift;
use App\Helpers\NotificationHelper2;

class SendPatrolReminders extends Command
{
    protected $signature = 'patrol:send-reminders';
    protected $description = 'Send patrol reminder notifications 30 minutes before shift start';

    public function handle()
    {
        $windowStart = now()->addMinutes(29)->format('H:i:s');
        $windowEnd = now()->addMinutes(31)->format('H:i:s');

        // Find active shifts starting in ~30 minutes
        $shifts = BuildingShift::where('status', 'Active')
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->pluck('id');

        if ($shifts->isEmpty()) {
            $this->info('No shifts found in the 30-minute window.');
            return;
        }

        // Find assignments for those shifts
        $assignments = GuardPatrolAssignment::whereIn('building_shift_id', $shifts)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->with(['guardUser', 'patrolLocation', 'buildingShift'])
            ->get();

        if ($assignments->isEmpty()) {
            $this->info('No assignments found for upcoming shifts.');
            return;
        }

        foreach ($assignments as $assignment) {
            try {
                NotificationHelper2::sendNotification(
                    $assignment->guard_user_id,
                    'Patrol Reminder',
                    'Your patrol at ' . $assignment->patrolLocation->name . ' starts in 30 minutes (' . $assignment->buildingShift->start_time . '). Please be ready.',
                    [],
                    ['save_to_db' => true, 'building_id' => $assignment->building_id, 'type' => 'patrol_reminder']
                );
                $this->info('Reminder sent to ' . $assignment->guardUser->name . ' for ' . $assignment->patrolLocation->name);
            } catch (\Exception $e) {
                $this->error('Failed to send reminder to guard ' . $assignment->guard_user_id . ': ' . $e->getMessage());
            }
        }

        $this->info('Patrol reminders sent successfully!');
    }
}
