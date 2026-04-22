<?php

namespace App\Http\Controllers\Admin;

use App\Models\DutyCheckin;
use App\Models\Building;
use App\Models\User;
use App\Models\Gate;
use App\Models\BuildingShift;
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

        // Apply filters
        if ($request->filled('guard_user_id')) {
            $query->where('guard_user_id', $request->guard_user_id);
        }

        if ($request->filled('gate_id')) {
            $query->where('gate_id', $request->gate_id);
        }

        if ($request->filled('building_shift_id')) {
            $query->where('building_shift_id', $request->building_shift_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('checked_in_at', $request->date);
        }

        // Eager load relationships
        $checkins = $query->with(['guardUser', 'gate', 'buildingShift'])
            ->orderBy('checked_in_at', 'desc')
            ->paginate(20);

        // Get filter dropdowns
        $guards = User::where('building_id', $building->id)
            ->where('role', 'guard')
            ->orderBy('first_name')
            ->get();

        $gates = Gate::where('building_id', $building->id)
            ->orderBy('name')
            ->get();

        $shifts = BuildingShift::where('building_id', $building->id)
            ->orderBy('name')
            ->get();

        return view('admin.duty_checkins.index', [
            'checkins' => $checkins,
            'building' => $building,
            'guards' => $guards,
            'gates' => $gates,
            'shifts' => $shifts,
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
}
