<?php

namespace App\Http\Controllers\Admin;

use App\Models\DutyCheckin;
use App\Models\Building;
use App\Models\User;
use App\Models\Gate;
use App\Models\BuildingShift;
use App\Models\GuardShiftLog;
use App\Models\ShiftHandover;
use Illuminate\Http\Request;

class DutyCheckinController
{
    public function index(Request $request)
    {
        $building = auth()->user()->building;

        if (!$building) {
            return redirect()->back()->with('error', 'Building not found');
        }

        $query = DutyCheckin::where('building_id', $building->id);

        if ($request->filled('guard_user_id'))     $query->where('guard_user_id', $request->guard_user_id);
        if ($request->filled('gate_id'))           $query->where('gate_id', $request->gate_id);
        if ($request->filled('building_shift_id')) $query->where('building_shift_id', $request->building_shift_id);
        if ($request->filled('status'))            $query->where('status', $request->status);
        if ($request->filled('date'))              $query->whereDate('checked_in_at', $request->date);

        $checkins = $query->with(['guardUser', 'gate', 'buildingShift'])
            ->orderBy('checked_in_at', 'desc')
            ->paginate(20);

        $guards = User::where('building_id', $building->id)->where('role', 'guard')->orderBy('first_name')->get();
        $gates  = Gate::where('building_id', $building->id)->orderBy('name')->get();
        $shifts = BuildingShift::where('building_id', $building->id)->orderBy('name')->get();

        return view('admin.duty_checkins.index', [
            'checkins' => $checkins,
            'building' => $building,
            'guards'   => $guards,
            'gates'    => $gates,
            'shifts'   => $shifts,
        ]);
    }

    public function updateInterval(Request $request)
    {
        $building = auth()->user()->building;
        if (!$building) {
            return redirect()->back()->with('error', 'Building not found');
        }

        $validated = $request->validate([
            'duty_checkin_interval_minutes' => 'required|integer|min:5|max:480',
        ]);

        $building->update(['duty_checkin_interval_minutes' => $validated['duty_checkin_interval_minutes']]);

        return redirect()->back()->with('success', 'Check-in interval updated successfully');
    }

    // ── Guard Shift Logs ─────────────────────────────────────────────────────

    public function shiftLogs(Request $request)
    {
        $building = auth()->user()->building;
        if (!$building) return redirect()->back()->with('error', 'Building not found');

        $query = GuardShiftLog::where('building_id', $building->id);

        if ($request->filled('guard_user_id'))     $query->where('guard_user_id', $request->guard_user_id);
        if ($request->filled('gate_id'))           $query->where('gate_id', $request->gate_id);
        if ($request->filled('building_shift_id')) $query->where('building_shift_id', $request->building_shift_id);
        if ($request->filled('status'))            $query->where('status', $request->status);
        if ($request->filled('date'))              $query->where('shift_date', $request->date);

        $logs = $query->with(['guardUser', 'gate', 'buildingShift'])
            ->orderBy('shift_date', 'desc')
            ->orderBy('checked_in_at', 'desc')
            ->paginate(25);

        $guards = User::where('building_id', $building->id)->where('role', 'guard')->orderBy('first_name')->get();
        $gates  = Gate::where('building_id', $building->id)->orderBy('name')->get();
        $shifts = BuildingShift::where('building_id', $building->id)->orderBy('name')->get();

        return view('admin.duty_checkins.shift_logs', compact('logs', 'guards', 'gates', 'shifts', 'building'));
    }

    // ── Handover Records ─────────────────────────────────────────────────────

    public function handovers(Request $request)
    {
        $building = auth()->user()->building;
        if (!$building) return redirect()->back()->with('error', 'Building not found');

        $query = ShiftHandover::where('building_id', $building->id);

        if ($request->filled('gate_id'))           $query->where('gate_id', $request->gate_id);
        if ($request->filled('building_shift_id')) $query->where('building_shift_id', $request->building_shift_id);
        if ($request->filled('status'))            $query->where('status', $request->status);
        if ($request->filled('date'))              $query->where('shift_date', $request->date);

        $handovers = $query->with(['outgoingGuard', 'incomingGuard', 'gate', 'buildingShift'])
            ->orderBy('shift_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $gates  = Gate::where('building_id', $building->id)->orderBy('name')->get();
        $shifts = BuildingShift::where('building_id', $building->id)->orderBy('name')->get();

        return view('admin.duty_checkins.handovers', compact('handovers', 'gates', 'shifts', 'building'));
    }

    // ── Monthly Attendance Report ─────────────────────────────────────────────

    public function attendanceReport(Request $request)
    {
        $building = auth()->user()->building;
        if (!$building) return redirect()->back()->with('error', 'Building not found');

        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year',  now()->year);

        $logs = GuardShiftLog::where('building_id', $building->id)
            ->whereYear('shift_date', $year)
            ->whereMonth('shift_date', $month)
            ->with(['guardUser', 'gate', 'buildingShift'])
            ->orderBy('shift_date')
            ->get();

        // Group by guard → summary
        $summary = [];
        foreach ($logs as $log) {
            $guardId   = $log->guard_user_id;
            $guardName = $log->guardUser?->name ?? $log->guardUser?->first_name ?? "Guard #{$guardId}";

            if (!isset($summary[$guardId])) {
                $summary[$guardId] = [
                    'name'               => $guardName,
                    'present'            => 0,
                    'late'               => 0,
                    'absent'             => 0,
                    'completed'          => 0,
                    'total_late_minutes' => 0,
                    'logs'               => [],
                ];
            }

            if ($log->status === 'absent') {
                $summary[$guardId]['absent']++;
            } elseif ($log->status === 'late') {
                $summary[$guardId]['late']++;
                $summary[$guardId]['present']++;
                $summary[$guardId]['total_late_minutes'] += $log->late_minutes;
            } elseif ($log->status === 'completed') {
                $summary[$guardId]['completed']++;
                $summary[$guardId]['present']++;
                if ($log->late_minutes > 0) {
                    $summary[$guardId]['late']++;
                    $summary[$guardId]['total_late_minutes'] += $log->late_minutes;
                }
            } elseif (in_array($log->status, ['active', 'handover_pending'])) {
                $summary[$guardId]['present']++;
                if ($log->late_minutes > 0) {
                    $summary[$guardId]['late']++;
                    $summary[$guardId]['total_late_minutes'] += $log->late_minutes;
                }
            }

            $summary[$guardId]['logs'][] = $log;
        }

        $guards = User::where('building_id', $building->id)->where('role', 'guard')->orderBy('first_name')->get();

        return view('admin.duty_checkins.attendance_report', compact('summary', 'month', 'year', 'building', 'guards'));
    }
}
