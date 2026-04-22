<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GuardPatrolAssignment;
use App\Models\BuildingShift;
use App\Models\PatrolLocation;
use App\Helpers\NotificationHelper2;

class SendPatrolReminders extends Command
{
    protected $signature = 'patrol:send-reminders';
    protected $description = 'Send patrol reminder notifications 30 minutes before shift start';

    public function handle()
    {
        // 1. Send 30-minute shift start reminders
        $windowStart = now()->addMinutes(29)->format('H:i:s');
        $windowEnd = now()->addMinutes(31)->format('H:i:s');

        // Find active shifts starting in ~30 minutes
        $shifts = BuildingShift::where('status', 'Active')
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->pluck('id');

        if (!$shifts->isEmpty()) {
            // Find assignments for those shifts
            $assignments = GuardPatrolAssignment::whereIn('building_shift_id', $shifts)
                ->where('status', 'Active')
                ->whereNull('deleted_at')
                ->with(['guardUser', 'patrolLocation', 'buildingShift'])
                ->get();

            foreach ($assignments as $assignment) {
                try {
                    NotificationHelper2::sendNotification(
                        $assignment->guard_user_id,
                        'Patrol Reminder',
                        'Your patrol at ' . $assignment->patrolLocation->name . ' starts in 30 minutes (' . $assignment->buildingShift->start_time . '). Please be ready.',
                        [],
                        ['save_to_db' => true, 'building_id' => $assignment->building_id, 'type' => 'patrol_reminder']
                    );
                    $this->info('30-min reminder sent to ' . ($assignment->guardUser->name ?? 'Guard ' . $assignment->guard_user_id) . ' for ' . ($assignment->patrolLocation->name ?? 'Location'));
                } catch (\Exception $e) {
                    $this->error('Failed to send 30-min reminder to guard ' . $assignment->guard_user_id . ': ' . $e->getMessage());
                }
            }
        }

        // 2. Send patrol time started notifications
        $patrolWindowStart = now()->format('H:i:s');
        $patrolWindowEnd = now()->addMinutes(1)->format('H:i:s');

        $locations = PatrolLocation::whereBetween('patrol_time', [$patrolWindowStart, $patrolWindowEnd])
            ->where('status', 'Active')
            ->get();

        foreach ($locations as $location) {
            // Find guards assigned to this gate + shift
            $assignments = GuardPatrolAssignment::where('gate_id', $location->gate_id)
                ->where('building_shift_id', $location->building_shift_id)
                ->where('status', 'Active')
                ->whereNull('deleted_at')
                ->with('guardUser')
                ->get();

            foreach ($assignments as $assignment) {
                try {
                    NotificationHelper2::sendNotification(
                        $assignment->guard_user_id,
                        'Patrol Time Started',
                        'Your scheduled patrol time has started. Please begin your patrol at the assigned gate.',
                        [],
                        ['save_to_db' => true, 'building_id' => $assignment->building_id, 'type' => 'patrol_reminder']
                    );
                    $this->info('Patrol time notification sent to ' . ($assignment->guardUser->name ?? 'Guard ' . $assignment->guard_user_id));
                } catch (\Exception $e) {
                    $this->error('Failed to send patrol time notification to guard ' . $assignment->guard_user_id . ': ' . $e->getMessage());
                }
            }
        }

        $this->info('All patrol reminders sent successfully!');
    }
}
