<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuildingShift;
use Illuminate\Http\Request;
use Auth;

class BuildingShiftController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role != 'BA') {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $user->building;
        $shifts = BuildingShift::where('building_id', $building->id)
            ->orderBy('start_time')
            ->get();

        // Fetch guards (via BuildingUser with guard role)
        $guardRole = $this->getOrCreateGuardRole();
        $guards = [];
        if ($guardRole) {
            $buildingUsers = \App\Models\BuildingUser::where('building_id', $building->id)
                ->where('role_id', $guardRole->id)
                ->with('user')
                ->get();
            $guards = $buildingUsers->pluck('user', 'user_id');
        }

        // Fetch gates for this building
        $gates = \App\Models\Gate::where('building_id', $building->id)->get();

        // Fetch all assignments for this building
        $assignments = \App\Models\GuardPatrolAssignment::where('building_id', $building->id)
            ->with(['guardUser', 'buildingShift', 'gate'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.building_shifts.index', compact('shifts', 'building', 'guards', 'gates', 'assignments'));
    }

    protected function getOrCreateGuardRole()
    {
        $role = \App\Models\Role::whereRaw("LOWER(TRIM(COALESCE(slug, ''))) = ?", ['guard'])->first();
        if ($role) {
            return $role;
        }

        $role = \App\Models\Role::whereRaw("LOWER(TRIM(COALESCE(slug, ''))) LIKE ?", ['%guard%'])->first();
        if ($role) {
            return $role;
        }

        try {
            $new = new \App\Models\Role();
            if (\Schema::hasColumn('roles', 'name')) {
                $new->name = 'Guard';
            }
            if (\Schema::hasColumn('roles', 'slug')) {
                $new->slug = 'guard';
            }
            $new->save();
            return $new;
        } catch (\Exception $e) {
            \Log::error('Failed to create guard role: ' . $e->getMessage());
            return null;
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role != 'BA') {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $user->building;

        $rules = [
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'status' => 'required|in:Active,Inactive',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        // Check for overlapping shifts
        $overlap = BuildingShift::where('building_id', $building->id)
            ->where('id', '!=', $request->id ?? 0)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where('start_time', '<', $request->end_time)
            ->where('end_time', '>', $request->start_time)
            ->first();

        if ($overlap) {
            return redirect()->back()->with('error', 'Shift overlaps with existing shift: ' . $overlap->name . ' (' . $overlap->start_time . ' - ' . $overlap->end_time . ')');
        }

        $shift = new BuildingShift();
        if ($request->id) {
            $shift = BuildingShift::withTrashed()->find($request->id);
            $msg = 'Shift updated successfully!';
        } else {
            $msg = 'Shift created successfully!';
        }

        $shift->building_id = $building->id;
        $shift->name = $request->name;
        $shift->start_time = $request->start_time;
        $shift->end_time = $request->end_time;
        $shift->status = $request->status;
        $shift->save();

        if ($shift->trashed()) {
            $shift->restore();
        }

        return redirect()->back()->with('success', $msg);
    }

    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role != 'BA') {
            return response()->json(['msg' => 'failed'], 403);
        }

        $building = $user->building;
        $shift = BuildingShift::where('id', $id)->where('building_id', $building->id)->first();

        if (!$shift) {
            return response()->json(['msg' => 'failed'], 404);
        }

        if ($request->action == 'delete') {
            $shift->delete();
        } else {
            $shift->restore();
        }

        return response()->json(['msg' => 'success'], 200);
    }
}
