<?php

namespace App\Http\Controllers\Api;

use App\Models\DutyCheckin;
use App\Models\GuardPatrolAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DutyCheckinController
{
    public function status(Request $request)
    {
        $user = Auth::user();

        if (!$user->gate) {
            return response()->json([
                'success' => false,
                'message' => 'Guard not assigned to a gate'
            ], 400);
        }

        $building = $user->gate->building;
        if (!$building) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found'
            ], 404);
        }

        $assignment = GuardPatrolAssignment::where('guard_user_id', $user->id)
            ->where('gate_id', $user->gate->id)
            ->first();

        if (!$assignment || !$assignment->building_shift_id) {
            return response()->json([
                'success' => false,
                'message' => 'No active shift assignment'
            ], 400);
        }

        $shift = $assignment->buildingShift;
        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found'
            ], 404);
        }

        // Check if guard is within shift time
        $now = Carbon::now();
        $shiftStart = $shift->start_time ? Carbon::parse($shift->start_time) : null;
        $shiftEnd = $shift->end_time ? Carbon::parse($shift->end_time) : null;

        if (!$shiftStart || !$shiftEnd) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid shift times'
            ], 400);
        }

        $isOnDuty = false;
        if ($shiftStart->hour < $shiftEnd->hour || ($shiftStart->hour == $shiftEnd->hour && $shiftStart->minute < $shiftEnd->minute)) {
            // Same day shift
            $isOnDuty = $now->gte($shiftStart) && $now->lt($shiftEnd);
        } else {
            // Overnight shift
            $isOnDuty = $now->gte($shiftStart) || $now->lt($shiftEnd);
        }

        if (!$isOnDuty) {
            return response()->json([
                'success' => false,
                'message' => 'Not within shift time'
            ], 400);
        }

        $interval = $building->duty_checkin_interval_minutes ?? 30;

        // Get last check-in for today
        $lastCheckin = DutyCheckin::where('building_id', $building->id)
            ->where('guard_user_id', $user->id)
            ->whereDate('checked_in_at', $now->toDateString())
            ->orderBy('checked_in_at', 'desc')
            ->first();

        // Calculate next check-in time
        if ($lastCheckin) {
            $nextCheckinAt = $lastCheckin->checked_in_at->addMinutes($interval);
        } else {
            $nextCheckinAt = $shiftStart;
        }

        $secondsRemaining = $nextCheckinAt->diffInSeconds($now, false);
        $isOverdue = $secondsRemaining < 0;

        // Get recent check-ins (last 5 today)
        $recentCheckins = DutyCheckin::where('building_id', $building->id)
            ->where('guard_user_id', $user->id)
            ->whereDate('checked_in_at', $now->toDateString())
            ->orderBy('checked_in_at', 'desc')
            ->take(5)
            ->get(['checked_in_at', 'status']);

        return response()->json([
            'success' => true,
            'interval_minutes' => $interval,
            'name' => $shift->name,
            'last_checkin_at' => $lastCheckin ? $lastCheckin->checked_in_at : null,
            'next_checkin_at' => $nextCheckinAt,
            'seconds_remaining' => abs($secondsRemaining),
            'is_overdue' => $isOverdue,
            'recent_checkins' => $recentCheckins,
        ]);
    }

    public function checkin(Request $request)
    {
        $user = Auth::user();

        if (!$user->gate) {
            return response()->json([
                'success' => false,
                'message' => 'Guard not assigned to a gate'
            ], 400);
        }

        $building = $user->gate->building;
        if (!$building) {
            return response()->json([
                'success' => false,
                'message' => 'Building not found'
            ], 404);
        }

        $assignment = GuardPatrolAssignment::where('guard_user_id', $user->id)
            ->where('gate_id', $user->gate->id)
            ->first();

        if (!$assignment || !$assignment->building_shift_id) {
            return response()->json([
                'success' => false,
                'message' => 'No active shift assignment'
            ], 400);
        }

        $shift = $assignment->buildingShift;
        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found'
            ], 404);
        }

        $now = Carbon::now();
        $interval = $building->duty_checkin_interval_minutes ?? 30;
        $graceperiod = 5; // 5 minutes grace period

        // Get last check-in for today
        $lastCheckin = DutyCheckin::where('building_id', $building->id)
            ->where('guard_user_id', $user->id)
            ->whereDate('checked_in_at', $now->toDateString())
            ->orderBy('checked_in_at', 'desc')
            ->first();

        // Calculate scheduled time
        if ($lastCheckin) {
            $scheduledAt = $lastCheckin->checked_in_at->addMinutes($interval);
        } else {
            $shiftStart = $shift->start_time ? Carbon::parse($shift->start_time) : null;
            $scheduledAt = $shiftStart;
        }

        // Determine status based on grace period
        $minutesAfterScheduled = $now->diffInMinutes($scheduledAt, false);
        $status = $minutesAfterScheduled <= $graceperiod ? 'on_time' : 'delayed';

        // Create duty check-in record
        $dutyCheckin = DutyCheckin::create([
            'building_id' => $building->id,
            'guard_user_id' => $user->id,
            'gate_id' => $user->gate->id,
            'building_shift_id' => $assignment->building_shift_id,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'checked_in_at' => $now,
        ]);

        // Get new countdown
        $nextCheckinAt = $now->addMinutes($interval);
        $secondsRemaining = $nextCheckinAt->diffInSeconds($now, false);

        return response()->json([
            'success' => true,
            'message' => 'Check-in recorded',
            'status' => $status,
            'next_checkin_at' => $nextCheckinAt,
            'seconds_remaining' => $secondsRemaining,
        ]);
    }
}
