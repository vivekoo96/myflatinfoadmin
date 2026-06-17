<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardPatrolAssignment;
use App\Models\GuardShiftLog;
use App\Models\ShiftHandover;
use App\Models\BuildingShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GuardShiftController extends Controller
{
    /**
     * Handover window: how many minutes before shift start an incoming guard can arrive.
     * Falls back to building setting or default 10 min.
     */
    private function handoverWindow($building): int
    {
        return $building->handover_window_minutes ?? 10;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/guard/my-shift
    // Returns the authenticated guard's active assignment + today's shift log status
    // ──────────────────────────────────────────────────────────────────────────
    public function myShift(Request $request)
    {
        $user     = Auth::user();
        $building = $user->gate?->building ?? $user->building;

        if (!$building) {
            return response()->json(['success' => false, 'message' => 'Building not found'], 404);
        }

        // Find active assignment
        $assignment = GuardPatrolAssignment::where('guard_user_id', $user->id)
            ->where('status', 'Active')
            ->with(['buildingShift', 'gate'])
            ->latest()
            ->first();

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'No active shift assignment found'], 404);
        }

        $today   = Carbon::today()->toDateString();
        $shiftLog = GuardShiftLog::where('guard_user_id', $user->id)
            ->where('shift_date', $today)
            ->where('assignment_id', $assignment->id)
            ->latest()
            ->first();

        // Pending handover for this guard (as outgoing)
        $pendingHandover = ShiftHandover::where('outgoing_guard_id', $user->id)
            ->where('shift_date', $today)
            ->where('status', 'pending_outgoing')
            ->with(['incomingGuard', 'gate', 'buildingShift'])
            ->first();

        $shift = $assignment->buildingShift;

        return response()->json([
            'success'         => true,
            'assignment'      => [
                'id'               => $assignment->id,
                'gate'             => $assignment->gate?->name,
                'shift_name'       => $shift?->name,
                'shift_start'      => $shift?->start_time ? substr($shift->start_time, 0, 5) : null,
                'shift_end'        => $shift?->end_time   ? substr($shift->end_time,   0, 5) : null,
            ],
            'shift_log'       => $shiftLog ? [
                'id'             => $shiftLog->id,
                'status'         => $shiftLog->status,
                'checked_in_at'  => $shiftLog->checked_in_at,
                'checked_out_at' => $shiftLog->checked_out_at,
                'late_minutes'   => $shiftLog->late_minutes,
            ] : null,
            'pending_handover' => $pendingHandover ? [
                'id'             => $pendingHandover->id,
                'incoming_guard' => $pendingHandover->incomingGuard?->name ?? $pendingHandover->incomingGuard?->first_name,
                'arrived_at'     => $pendingHandover->incoming_arrived_at,
            ] : null,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/guard/shift-checkin
    // Guard confirms shift start → creates GuardShiftLog with status=active
    // Body: {} (empty — uses authenticated user's assignment)
    // ──────────────────────────────────────────────────────────────────────────
    public function shiftCheckin(Request $request)
    {
        $user     = Auth::user();
        $building = $user->gate?->building ?? $user->building;

        if (!$building) {
            return response()->json(['success' => false, 'message' => 'Building not found'], 404);
        }

        $assignment = GuardPatrolAssignment::where('guard_user_id', $user->id)
            ->where('status', 'Active')
            ->with(['buildingShift', 'gate'])
            ->latest()
            ->first();

        if (!$assignment || !$assignment->buildingShift) {
            return response()->json(['success' => false, 'message' => 'No active shift assignment'], 400);
        }

        $today = Carbon::today()->toDateString();

        // Prevent double check-in
        $existing = GuardShiftLog::where('guard_user_id', $user->id)
            ->where('shift_date', $today)
            ->where('assignment_id', $assignment->id)
            ->whereIn('status', ['active', 'handover_pending', 'completed'])
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Already checked in for this shift today'], 400);
        }

        $shift      = $assignment->buildingShift;
        $now        = Carbon::now();
        $shiftStart = Carbon::parse($today . ' ' . $shift->start_time);

        // Calculate lateness
        $lateMinutes = max(0, (int) $shiftStart->diffInMinutes($now, false));
        $status      = $lateMinutes > 0 ? 'late' : 'active';

        $log = GuardShiftLog::create([
            'building_id'       => $building->id,
            'guard_user_id'     => $user->id,
            'gate_id'           => $assignment->gate_id,
            'building_shift_id' => $assignment->building_shift_id,
            'assignment_id'     => $assignment->id,
            'shift_date'        => $today,
            'status'            => $status,
            'checked_in_at'     => $now,
            'late_minutes'      => $lateMinutes,
        ]);

        return response()->json([
            'success'      => true,
            'message'      => $lateMinutes > 0
                ? "Checked in — {$lateMinutes} minute(s) late"
                : 'Shift started successfully. Attendance is now active.',
            'status'       => $status,
            'late_minutes' => $lateMinutes,
            'log_id'       => $log->id,
            'checked_in_at'=> $now->toDateTimeString(),
            'shift_time'   => $shift->start_time,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/guard/submit-takeover
    // Bulk confirm shift start for selected assignments from the Shift Takeover UI
    // Body: { assignment_ids: [1, 2] }
    // ──────────────────────────────────────────────────────────────────────────
    public function submitTakeover(Request $request)
    {
        $request->validate([
            'assignment_ids'   => 'required|array',
            'assignment_ids.*' => 'exists:guard_patrol_assignments,id',
        ]);

        $now = Carbon::now();
        $today = Carbon::today()->toDateString();

        $timelineAdditions = [];

        foreach ($request->assignment_ids as $assignmentId) {
            $assignment = GuardPatrolAssignment::with(['buildingShift', 'guardUser'])->find($assignmentId);
            if (!$assignment || !$assignment->guardUser) continue;

            $existing = GuardShiftLog::where('guard_user_id', $assignment->guard_user_id)
                ->where('shift_date', $today)
                ->where('assignment_id', $assignment->id)
                ->first();

            if ($existing) continue;

            $shift = $assignment->buildingShift;
            $shiftStart = $shift ? Carbon::parse($today . ' ' . $shift->start_time) : clone $now;
            
            $lateMinutes = max(0, (int) $shiftStart->diffInMinutes($now, false));
            $status = $lateMinutes > 0 ? 'late' : 'active';

            $log = GuardShiftLog::create([
                'building_id'       => $assignment->building_id,
                'guard_user_id'     => $assignment->guard_user_id,
                'gate_id'           => $assignment->gate_id,
                'building_shift_id' => $assignment->building_shift_id,
                'assignment_id'     => $assignment->id,
                'shift_date'        => $today,
                'status'            => $status,
                'checked_in_at'     => $now,
                'late_minutes'      => $lateMinutes,
            ]);

            $guardUser = $assignment->guardUser;
            $guardName = $guardUser->name ?? trim($guardUser->first_name . ' ' . $guardUser->last_name);

            $timelineAdditions[] = [
                'guard_name'    => $guardName,
                'action'        => $lateMinutes > 0 ? "Shift Started ({$lateMinutes}m late)" : 'Shift Started',
                'time'          => $now->format('d M Y  h:i a'),
                'timestamp'     => $now->toDateTimeString(),
                'late_minutes'  => $lateMinutes,
                'shift_time'    => $shift ? Carbon::parse($shift->start_time)->format('h:i a') : null
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Duty start confirmed successfully.',
            'timeline_additions' => $timelineAdditions
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/guard/handover/initiate
    // Incoming guard arrives at gate before their shift starts.
    // Allowed within `handover_window_minutes` before shift start, or after.
    // Body: { building_shift_id, gate_id }
    // ──────────────────────────────────────────────────────────────────────────
    public function initiateHandover(Request $request)
    {
        $request->validate([
            'building_shift_id' => 'required|exists:building_shifts,id',
            'gate_id'           => 'required|exists:gates,id',
        ]);

        $user     = Auth::user();
        $building = $user->gate?->building ?? $user->building;

        if (!$building) {
            return response()->json(['success' => false, 'message' => 'Building not found'], 404);
        }

        $shift  = BuildingShift::find($request->building_shift_id);
        $today  = Carbon::today()->toDateString();
        $now    = Carbon::now();
        $window = $this->handoverWindow($building);

        $shiftStart = Carbon::parse($today . ' ' . $shift->start_time);
        $allowedFrom = $shiftStart->copy()->subMinutes($window);

        // Guard can arrive within window or after shift starts
        if ($now->lt($allowedFrom)) {
            $minutesUntilAllowed = (int) $now->diffInMinutes($allowedFrom);
            return response()->json([
                'success' => false,
                'message' => "Too early. You can arrive {$window} minutes before shift start. Please wait {$minutesUntilAllowed} more minute(s).",
            ], 400);
        }

        // Prevent duplicate
        $existing = ShiftHandover::where('incoming_guard_id', $user->id)
            ->where('building_shift_id', $request->building_shift_id)
            ->where('gate_id', $request->gate_id)
            ->where('shift_date', $today)
            ->whereIn('status', ['pending_incoming', 'pending_outgoing'])
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Handover already initiated'], 400);
        }

        // Find the outgoing guard's shift log for this gate + shift today
        $outgoingLog = GuardShiftLog::where('gate_id', $request->gate_id)
            ->where('building_shift_id', $request->building_shift_id)
            ->where('shift_date', $today)
            ->whereIn('status', ['active', 'late'])
            ->latest()
            ->first();

        // Late minutes for incoming guard
        $lateMinutes = max(0, (int) $shiftStart->diffInMinutes($now, false));

        $handover = ShiftHandover::create([
            'building_id'        => $building->id,
            'gate_id'            => $request->gate_id,
            'building_shift_id'  => $request->building_shift_id,
            'shift_date'         => $today,
            'outgoing_guard_id'  => $outgoingLog?->guard_user_id ?? 0,
            'incoming_guard_id'  => $user->id,
            'outgoing_log_id'    => $outgoingLog?->id,
            'incoming_arrived_at'=> $now,
            'status'             => 'pending_outgoing',
            'late_minutes'       => $lateMinutes,
        ]);

        return response()->json([
            'success'            => true,
            'message'            => $lateMinutes > 0
                ? "Arrived {$lateMinutes} min late. Waiting for outgoing guard to confirm handover."
                : 'Arrived on time. Waiting for outgoing guard to confirm handover.',
            'handover_id'        => $handover->id,
            'late_minutes'       => $lateMinutes,
            'outgoing_guard_id'  => $handover->outgoing_guard_id,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/guard/handover/incoming-confirm
    // Incoming guard confirms they are taking over the shift.
    // Body: { handover_id }
    // ──────────────────────────────────────────────────────────────────────────
    public function incomingConfirm(Request $request)
    {
        $request->validate([
            'handover_id' => 'required|exists:shift_handovers,id',
        ]);

        $user     = Auth::user();
        $handover = ShiftHandover::where('id', $request->handover_id)
            ->where('incoming_guard_id', $user->id)
            ->with(['buildingShift'])
            ->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Handover not found'], 404);
        }

        if ($handover->status !== 'pending_outgoing') {
            return response()->json(['success' => false, 'message' => 'Handover is not in the correct state'], 400);
        }

        $now   = Carbon::now();
        $today = Carbon::today()->toDateString();

        // Find the incoming guard's assignment for this shift+gate
        $assignment = GuardPatrolAssignment::where('guard_user_id', $user->id)
            ->where('gate_id', $handover->gate_id)
            ->where('building_shift_id', $handover->building_shift_id)
            ->where('status', 'Active')
            ->first();

        // Create the incoming guard's shift log
        $shift      = $handover->buildingShift;
        $shiftStart = Carbon::parse($today . ' ' . $shift->start_time);
        $lateMinutes = max(0, (int) $shiftStart->diffInMinutes($now, false));
        $status      = $lateMinutes > 0 ? 'late' : 'active';

        $incomingLog = GuardShiftLog::create([
            'building_id'           => $handover->building_id,
            'guard_user_id'         => $user->id,
            'gate_id'               => $handover->gate_id,
            'building_shift_id'     => $handover->building_shift_id,
            'assignment_id'         => $assignment?->id,
            'shift_date'            => $today,
            'status'                => $status,
            'checked_in_at'         => $now,
            'late_minutes'          => $lateMinutes,
            'handover_confirmed_by' => $user->id,
        ]);

        // Update handover record
        $handover->update([
            'incoming_log_id'       => $incomingLog->id,
            'incoming_confirmed_at' => $now,
            // Status now waits for outgoing guard to confirm
            'status'                => 'pending_outgoing',
        ]);

        return response()->json([
            'success'         => true,
            'message'         => 'Takeover confirmed. Your attendance has started. Waiting for outgoing guard to confirm handover.',
            'log_id'          => $incomingLog->id,
            'status'          => $status,
            'late_minutes'    => $lateMinutes,
            'checked_in_at'   => $now->toDateTimeString(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/guard/handover/outgoing-confirm
    // Outgoing guard confirms they have handed over the shift.
    // Body: { handover_id }
    // ──────────────────────────────────────────────────────────────────────────
    public function outgoingConfirm(Request $request)
    {
        $request->validate([
            'handover_id' => 'required|exists:shift_handovers,id',
        ]);

        $user     = Auth::user();
        $handover = ShiftHandover::where('id', $request->handover_id)
            ->where('outgoing_guard_id', $user->id)
            ->first();

        if (!$handover) {
            return response()->json(['success' => false, 'message' => 'Handover not found'], 404);
        }

        if (!in_array($handover->status, ['pending_outgoing'])) {
            return response()->json(['success' => false, 'message' => 'Handover is not awaiting your confirmation'], 400);
        }

        if (!$handover->incoming_confirmed_at) {
            return response()->json(['success' => false, 'message' => 'Incoming guard has not confirmed takeover yet'], 400);
        }

        $now = Carbon::now();

        // Mark outgoing guard's shift log as completed
        if ($handover->outgoing_log_id) {
            GuardShiftLog::where('id', $handover->outgoing_log_id)->update([
                'status'         => 'completed',
                'checked_out_at' => $now,
                'handover_at'    => $now,
            ]);
        } else {
            // Fallback: find today's active log for outgoing guard
            GuardShiftLog::where('guard_user_id', $user->id)
                ->where('gate_id', $handover->gate_id)
                ->where('shift_date', $handover->shift_date->toDateString())
                ->whereIn('status', ['active', 'late', 'handover_pending'])
                ->update([
                    'status'         => 'completed',
                    'checked_out_at' => $now,
                    'handover_at'    => $now,
                ]);
        }

        // Mark handover as completed
        $handover->update([
            'outgoing_confirmed_at' => $now,
            'status'                => 'completed',
        ]);

        return response()->json([
            'success'        => true,
            'message'        => 'Handover confirmed. Your shift has ended. Attendance recorded.',
            'checked_out_at' => $now->toDateTimeString(),
        ]);
    }
    private function formatGuards($assignments)
    {
        $today = Carbon::today()->toDateString();
        $guards = [];
        $timeline = [];

        foreach ($assignments as $assignment) {
            $guardUser = $assignment->guardUser;
            if (!$guardUser) continue;

            $log = GuardShiftLog::where('guard_user_id', $guardUser->id)
                ->where('assignment_id', $assignment->id)
                ->where('shift_date', $today)
                ->latest()
                ->first();

            $isStarted = $log && in_array($log->status, ['active', 'late', 'completed', 'handover_pending']);
            $guardName = $guardUser->name ?? trim($guardUser->first_name . ' ' . $guardUser->last_name);

            $guards[] = [
                'assignment_id' => $assignment->id,
                'guard_id'      => $guardUser->id,
                'name'          => $guardName,
                'is_started'    => $isStarted,
                'notes'         => $assignment->notes ?? 'Security Guard',
            ];

            if ($isStarted) {
                $timeline[] = [
                    'guard_name'    => $guardName,
                    'action'        => 'Shift Started',
                    'time'          => Carbon::parse($log->checked_in_at)->format('d M Y  h:i a'),
                    'timestamp'     => $log->checked_in_at,
                ];
            }
        }

        usort($timeline, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        return [
            'guards'   => $guards,
            'timeline' => $timeline,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/guard/gate-shift-guards
    // Returns the guards assigned to the guard's current gate and shift today
    // ──────────────────────────────────────────────────────────────────────────
    public function gateShiftGuards(Request $request)
    {
        $user = Auth::user();
        $currentAssignment = GuardPatrolAssignment::where('guard_user_id', $user->id)
            ->where('status', 'Active')
            ->first();

        if (!$currentAssignment) {
            return response()->json(['success' => false, 'message' => 'No active assignment found for the current guard.'], 404);
        }

        $assignments = GuardPatrolAssignment::where('gate_id', $currentAssignment->gate_id)
            ->where('building_shift_id', $currentAssignment->building_shift_id)
            ->where('status', 'Active')
            ->with(['guardUser'])
            ->get();

        $data = $this->formatGuards($assignments);

        return response()->json([
            'success'  => true,
            'guards'   => $data['guards'],
            'timeline' => $data['timeline'],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/guard/gate-guards
    // Returns the guards assigned to the guard's current gate today
    // ──────────────────────────────────────────────────────────────────────────
    public function gateGuards(Request $request)
    {
        $user = Auth::user();
        $currentAssignment = GuardPatrolAssignment::where('guard_user_id', $user->id)
            ->where('status', 'Active')
            ->first();

        if (!$currentAssignment) {
            return response()->json(['success' => false, 'message' => 'No active assignment found for the current guard.'], 404);
        }

        $assignments = GuardPatrolAssignment::where('gate_id', $currentAssignment->gate_id)
            ->where('status', 'Active')
            ->with(['guardUser'])
            ->get();

        $data = $this->formatGuards($assignments);

        return response()->json([
            'success'  => true,
            'guards'   => $data['guards'],
            'timeline' => $data['timeline'],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/guard/shift-guards
    // Returns the guards assigned to the guard's current shift today
    // ──────────────────────────────────────────────────────────────────────────
    public function shiftGuards(Request $request)
    {
        $user = Auth::user();
        $currentAssignment = GuardPatrolAssignment::where('guard_user_id', $user->id)
            ->where('status', 'Active')
            ->first();

        if (!$currentAssignment) {
            return response()->json(['success' => false, 'message' => 'No active assignment found for the current guard.'], 404);
        }

        $assignments = GuardPatrolAssignment::where('building_shift_id', $currentAssignment->building_shift_id)
            ->where('status', 'Active')
            ->with(['guardUser'])
            ->get();

        $data = $this->formatGuards($assignments);

        return response()->json([
            'success'  => true,
            'guards'   => $data['guards'],
            'timeline' => $data['timeline'],
        ]);
    }
}
