<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatrolLocation;
use App\Models\Gate;
use App\Models\BuildingShift;
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
            ->withTrashed()
            ->orderBy('name')
            ->get();

        $gates = Gate::where('building_id', $building->id)->get();
        $shifts = BuildingShift::where('building_id', $building->id)
            ->where('status', 'Active')
            ->orderBy('start_time')
            ->get();

        return view('admin.patrol_locations.index', compact('building', 'locations', 'gates', 'shifts'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'BA') {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = Auth::user()->building;

        // Validate patrol schedule
        $rules = [
            'gate_id' => 'required|exists:gates,id',
            'building_shift_id' => 'required|exists:building_shifts,id',
            'patrol_time' => 'required|date_format:H:i',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        // Verify gate and shift belong to this building
        $gate = Gate::where('id', $request->gate_id)->where('building_id', $building->id)->first();
        if (!$gate) {
            return redirect()->back()->with('error', 'Gate not found for this building');
        }

        $shift = BuildingShift::where('id', $request->building_shift_id)->where('building_id', $building->id)->first();
        if (!$shift) {
            return redirect()->back()->with('error', 'Shift not found for this building');
        }

        // Validate patrol_time is within shift hours
        if ($request->patrol_time < $shift->start_time || $request->patrol_time > $shift->end_time) {
            return redirect()->back()->with('error', 'Patrol time must be between ' . $shift->start_time . ' and ' . $shift->end_time);
        }

        $msg = 'Patrol schedule added successfully';
        $location = new PatrolLocation();
        $isNew = false;

        if ($request->id) {
            $location = PatrolLocation::where('id', $request->id)->where('building_id', $building->id)->first();
            if (!$location) {
                return redirect()->back()->with('error', 'Patrol schedule not found');
            }
            $msg = 'Patrol schedule updated successfully';
        } else {
            // Generate unique QR string only on creation
            $location->qr_string = Str::random(32);
            $isNew = true;
        }

        // Auto-generate name from gate, shift, and time
        $name = $gate->name . ' - ' . $shift->name . ' - ' . $request->patrol_time;

        $location->building_id = $building->id;
        $location->name = $name;
        $location->gate_id = $request->gate_id;
        $location->building_shift_id = $request->building_shift_id;
        $location->patrol_time = $request->patrol_time;
        $location->status = 'Active';
        $location->save();

        // Notify guards assigned to this gate + shift if new schedule
        if ($isNew) {
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
