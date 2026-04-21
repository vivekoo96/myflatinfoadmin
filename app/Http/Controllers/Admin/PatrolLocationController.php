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

        // Check if this is an assignment (patrol_location_id provided) or location creation
        if ($request->patrol_location_id) {
            // Handle assignment
            $rules = [
                'patrol_location_id' => 'required|exists:patrol_locations,id',
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

            $location = PatrolLocation::where('id', $request->patrol_location_id)->where('building_id', $building->id)->first();
            if (!$location) {
                return redirect()->back()->with('error', 'Location not found for this building');
            }

            $location->gate_id = $request->gate_id;
            $location->building_shift_id = $request->building_shift_id;
            $location->patrol_time = $request->patrol_time;
            $location->save();

            return redirect()->back()->with('success', 'Location assigned to gate and shift successfully');
        } else {
            // Handle location creation/update
            $rules = [
                'name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'status' => 'required|in:Active,Inactive',
            ];

            $validation = \Validator::make($request->all(), $rules);
            if ($validation->fails()) {
                return redirect()->back()->with('error', $validation->errors()->first());
            }

            $msg = 'Patrol location added successfully';
            $location = new PatrolLocation();

            if ($request->id) {
                $location = PatrolLocation::withTrashed()->find($request->id);
                $msg = 'Patrol location updated';
            } else {
                // Generate unique QR string only on creation
                $location->qr_string = Str::random(32);
            }

            $location->building_id = $building->id;
            $location->name = $request->name;
            $location->description = $request->description;
            $location->status = $request->status;
            $location->save();

            return redirect()->back()->with('success', $msg);
        }
    }

    public function show($id)
    {
        $location = PatrolLocation::where('id', $id)
            ->where('building_id', Auth::user()->building_id)
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
