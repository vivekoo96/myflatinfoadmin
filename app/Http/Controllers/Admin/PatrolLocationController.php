<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatrolLocation;
use App\Models\Gate;
use App\Models\BuildingShift;
use App\Models\GuardPatrol;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Auth;

class PatrolLocationController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'BA' && !Auth::user()->hasRole('security')) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;
        $locations = PatrolLocation::where('building_id', $building->id)
            ->with(['gate', 'buildingShift'])
            ->orderBy('name')
            ->get();

        $gates = Gate::where('building_id', $building->id)->get();
        $shifts = BuildingShift::where('building_id', $building->id)
            ->where('status', 'Active')
            ->orderBy('start_time')
            ->get();

        // Get today's patrol progress for each schedule
        $scheduleProgress = [];
        foreach ($locations->where('gate_id', '!=', null) as $schedule) {
            if (!$schedule->gate_id || !$schedule->building_shift_id) continue;

            $key = $schedule->gate_id . '_' . $schedule->building_shift_id;
            if (!isset($scheduleProgress[$key])) {
                $allLocations = PatrolLocation::where('gate_id', $schedule->gate_id)
                    ->where('status', 'Active')
                    ->get();

                $completedCount = 0;
                foreach ($allLocations as $loc) {
                    $checked = GuardPatrol::where('patrol_location_id', $loc->id)
                        ->whereDate('checked_in_at', today())
                        ->exists();
                    if ($checked) $completedCount++;
                }

                $scheduleProgress[$key] = [
                    'completed' => $completedCount,
                    'total' => $allLocations->count(),
                ];
            }
        }

        return view('admin.patrol_locations.index', compact('building', 'locations', 'gates', 'shifts', 'scheduleProgress'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'BA') {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;

        // Validate
        $rules = [
            'name' => 'required_without:gate_id',
            'gate_id' => 'nullable|exists:gates,id',
            'building_shift_id' => 'required_with:gate_id|nullable|exists:building_shifts,id',
            'patrol_time' => 'required_with:gate_id|nullable|date_format:H:i',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        $name = $request->name;
        $gate = null;
        $shift = null;

        if ($request->gate_id) {
            // Verify gate and shift belong to this building
            $gate = Gate::where('id', $request->gate_id)->where('building_id', $building->id)->first();
            if (!$gate) {
                return redirect()->back()->with('error', 'Gate not found for this building');
            }

            $shift = BuildingShift::where('id', $request->building_shift_id)->where('building_id', $building->id)->first();
            if (!$shift) {
                return redirect()->back()->with('error', 'Shift not found for this building');
            }

             // Validate patrol_time is within shift hours (handling overnight shifts)
             $tToMin = function($t) {
                 $parts = explode(':', $t);
                 return intval($parts[0]) * 60 + intval($parts[1]);
             };

             $startMin = $tToMin($shift->start_time);
             $endMin = $tToMin($shift->end_time);
             $patrolMin = $tToMin($request->patrol_time);

             $isValid = false;
             if ($startMin <= $endMin) {
                 $isValid = ($patrolMin >= $startMin && $patrolMin <= $endMin);
             } else {
                 $isValid = ($patrolMin >= $startMin || $patrolMin <= $endMin);
             }

             if (!$isValid) {
                 $startFormatted = date('h:i A', strtotime($shift->start_time));
                 $endFormatted = date('h:i A', strtotime($shift->end_time));
                 return redirect()->back()->with('error', 'Patrol time must be between ' . $startFormatted . ' and ' . $endFormatted);
             }

            // Auto-generate name from gate, shift, and time
            $name = $gate->name . ' - ' . $shift->name . ' - ' . $request->patrol_time;
        }

        $msg = 'Patrol item added successfully';
        $location = new PatrolLocation();
        $isNew = false;

        if ($request->id) {
            $location = PatrolLocation::where('id', $request->id)->where('building_id', $building->id)->first();
            if (!$location) {
                return redirect()->back()->with('error', 'Patrol item not found');
            }
            $msg = 'Patrol item updated successfully';
        } else {
            // Generate unique QR string only on creation
            $location->qr_string = Str::random(32);
            $isNew = true;
        }

        $location->building_id = $building->id;
        $location->name = $name;
        $location->description = $request->description;
        $location->gate_id = $request->gate_id;
        $location->building_shift_id = $request->building_shift_id;
        $location->patrol_time = $request->patrol_time;
        $location->status = $request->status ?? 'Active';
        $location->save();

        // Notify guards assigned to this gate + shift if new schedule
        if ($isNew && $location->gate_id) {
            $assignments = \App\Models\GuardPatrolAssignment::where('gate_id', $location->gate_id)
                ->where('building_shift_id', $location->building_shift_id)
                ->where('building_id', $building->id)
                ->where('status', 'Active')
                ->whereNull('deleted_at')
                ->get();

            foreach ($assignments as $assignment) {
                \App\Helpers\NotificationHelper2::sendNotification(
                    $assignment->guard_user_id,
                    'Patrol Schedule Assigned',
                    'A new patrol schedule has been created for your assigned gate and shift. Please check your schedule details and be prepared accordingly.',
                    [],
                    ['save_to_db' => true, 'building_id' => $building->id, 'type' => 'patrol_schedule']
                );
            }
        }

        return redirect()->back()->with('success', $msg);
    }

    public function show($id)
    {
        $location = PatrolLocation::where('id', $id)
            ->where('building_id', Auth::user()->building_id)
            ->withTrashed()
            ->firstOrFail();

        return response()->json([
            'id' => $location->id,
            'name' => $location->name,
            'qr_string' => $location->qr_string,
        ]);
    }

    public function destroy($id, Request $request)
    {
        if (Auth::user()->role !== 'BA') {
            return response()->json(['msg' => 'error', 'detail' => 'Permission denied'], 403);
        }

        $location = PatrolLocation::where('id', $id)
            ->where('building_id', Auth::user()->building_id)
            ->withTrashed()
            ->firstOrFail();

        if ($request->remove_assignment) {
            // Only remove the assignment (clear gate/shift/time)
            $location->gate_id = null;
            $location->building_shift_id = null;
            $location->patrol_time = null;
            $location->save();
        } else {
            // Delete the location entirely
            $location->delete();
        }

        return response()->json(['msg' => 'success']);
    }
}
