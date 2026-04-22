<?php

namespace App\Helpers;

use App\Models\DutyCheckin;
use Carbon\Carbon;

class DutyCheckinHelper
{
    public static function checkAndCreateMissedCheckins($building, $guardUser, $assignment, $now = null)
    {
        $now = $now ?? Carbon::now();
        $interval = $building->duty_checkin_interval_minutes ?? 30;

        // Get all check-ins for today
        $checkins = DutyCheckin::where('building_id', $building->id)
            ->where('guard_user_id', $guardUser->id)
            ->whereDate('checked_in_at', $now->toDateString())
            ->orderBy('checked_in_at', 'asc')
            ->get();

        $missed = [];

        if ($checkins->isEmpty()) {
            // No check-ins yet today, check if shift has started
            $shift = $assignment->buildingShift;
            if ($shift) {
                $shiftStart = Carbon::parse($shift->start_time);
                $timeSinceShiftStart = $now->diffInMinutes($shiftStart);

                // If shift started and more than interval has passed without check-in
                if ($timeSinceShiftStart > $interval && $now->gte($shiftStart)) {
                    $missed[] = [
                        'scheduled_at' => $shiftStart,
                        'building_id' => $building->id,
                        'guard_user_id' => $guardUser->id,
                        'gate_id' => $assignment->gate_id,
                        'building_shift_id' => $assignment->building_shift_id,
                    ];
                }
            }
        } else {
            // Check for gaps between check-ins
            foreach ($checkins as $i => $checkin) {
                if ($i === 0) continue;

                $prevCheckin = $checkins[$i - 1];
                $expectedNextCheckin = $prevCheckin->checked_in_at->addMinutes($interval);

                // If there's a gap > interval
                if ($checkin->checked_in_at->diffInMinutes($expectedNextCheckin) > 0) {
                    $missed[] = [
                        'scheduled_at' => $expectedNextCheckin,
                        'building_id' => $building->id,
                        'guard_user_id' => $guardUser->id,
                        'gate_id' => $assignment->gate_id,
                        'building_shift_id' => $assignment->building_shift_id,
                    ];
                }
            }

            // Check if new check-in is overdue
            $lastCheckin = $checkins->last();
            $expectedNextCheckin = $lastCheckin->checked_in_at->addMinutes($interval);

            if ($now->gt($expectedNextCheckin)) {
                $timePastExpected = $now->diffInMinutes($expectedNextCheckin);

                // If more than interval has passed, mark as missed
                if ($timePastExpected > $interval) {
                    $missed[] = [
                        'scheduled_at' => $expectedNextCheckin,
                        'building_id' => $building->id,
                        'guard_user_id' => $guardUser->id,
                        'gate_id' => $assignment->gate_id,
                        'building_shift_id' => $assignment->building_shift_id,
                    ];
                }
            }
        }

        // Create missed check-in records and send notifications
        foreach ($missed as $missData) {
            $existingMiss = DutyCheckin::where('building_id', $missData['building_id'])
                ->where('guard_user_id', $missData['guard_user_id'])
                ->where('scheduled_at', $missData['scheduled_at'])
                ->where('status', 'missed')
                ->exists();

            if (!$existingMiss) {
                DutyCheckin::create(array_merge($missData, [
                    'status' => 'missed',
                    'checked_in_at' => $now,
                ]));

                // Send notification to BA and Security
                self::sendMissedCheckinNotification($guardUser, $building, $missData);
            }
        }

        return $missed;
    }

    public static function sendMissedCheckinNotification($guard, $building, $missData)
    {
        // Get all BAs and Security staff for this building
        $staff = \App\Models\User::where('building_id', $building->id)
            ->whereIn('role', ['BA', 'security'])
            ->get();

        $guardName = $guard->name ?? ($guard->first_name ?? '') . ' ' . ($guard->last_name ?? '');
        $message = "{$guardName} missed a duty check-in at " . Carbon::parse($missData['scheduled_at'])->format('H:i');

        foreach ($staff as $user) {
            NotificationHelper2::sendNotification(
                $user->id,
                'Missed Duty Check-In',
                $message,
                [
                    'guard_user_id' => $guard->id,
                    'building_id' => $building->id,
                    'missed_time' => $missData['scheduled_at'],
                ],
                [
                    'save_to_db' => true,
                    'building_id' => $building->id,
                    'type' => 'missed_checkin',
                ]
            );
        }
    }
}
