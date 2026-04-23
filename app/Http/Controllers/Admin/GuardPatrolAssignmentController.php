<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuardPatrolAssignment;
use App\Models\BuildingShift;
use App\Models\PatrolLocation;
use App\Models\BuildingUser;
use App\Models\Role;
use Illuminate\Http\Request;
use Auth;
use App\Helpers\NotificationHelper2;

class GuardPatrolAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!($user->role == 'BA' || ($user->selectedRole && $user->selectedRole->name == 'Security'))) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $user->building;

        // Build guards list
        $guardRole = $this->getOrCreateGuardRole();
        $guards = [];
        if ($guardRole) {
            $buildingUsers = BuildingUser::where('building_id', $building->id)
                ->where('role_id', $guardRole->id)
                ->with('user')
                ->get();
            $guards = $buildingUsers->pluck('user', 'user_id');
        }

        // Get active locations and shifts
        $locations = PatrolLocation::where('building_id', $building->id)
            ->where('status', 'Active')
            ->get();

        $shifts = BuildingShift::where('building_id', $building->id)
            ->where('status', 'Active')
            ->orderBy('start_time')
            ->get();

        // Get all assignments
        $assignments = GuardPatrolAssignment::where('building_id', $building->id)
            ->with(['guardUser', 'patrolLocation', 'buildingShift'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.patrol_assignments.index', compact('assignments', 'guards', 'locations', 'shifts', 'building'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!($user->role == 'BA' || ($user->selectedRole && $user->selectedRole->name == 'Security'))) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $user->building;

        $rules = [
            'guard_user_id' => 'required|exists:users,id',
            'gate_id' => 'required|exists:gates,id',
            'building_shift_id' => 'required|exists:building_shifts,id',
            'notes' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        // Verify gate belongs to this building
        $gate = \App\Models\Gate::where('id', $request->gate_id)
            ->where('building_id', $building->id)
            ->first();

        if (!$gate) {
            return redirect()->back()->with('error', 'Gate not found for this building');
        }

        // Verify shift belongs to this building
        $shift = BuildingShift::where('id', $request->building_shift_id)
            ->where('building_id', $building->id)
            ->first();

        if (!$shift) {
            return redirect()->back()->with('error', 'Shift not found for this building');
        }

        // Check if THIS SAME GUARD already has an assignment in THIS SAME shift (prevent multiple gates per guard per shift)
        $existingAssignment = GuardPatrolAssignment::where('guard_user_id', $request->guard_user_id)
            ->where('building_shift_id', $request->building_shift_id)
            ->where('status', 'Active')
            ->whereNull('deleted_at');

        if ($request->id) {
            $existingAssignment = $existingAssignment->where('id', '!=', $request->id);
        }

        $conflict = $existingAssignment->with('gate')->first();
        if ($conflict) {
            $gateName = $conflict->gate->name ?? 'another gate';
            return redirect()->back()->with('error', "This guard is already assigned to {$gateName} for this shift. One guard cannot be assigned to multiple gates in the same shift.");
        }

        $oldGuardId = null;
        $assignment = new GuardPatrolAssignment();

        if ($request->id) {
            $assignment = GuardPatrolAssignment::find($request->id);
            $oldGuardId = $assignment->guard_user_id;
            $msg = 'Assignment updated successfully';
        } else {
            $msg = 'Assignment created successfully';
        }

        $assignment->building_id = $building->id;
        $assignment->guard_user_id = $request->guard_user_id;
        $assignment->gate_id = $request->gate_id;
        $assignment->building_shift_id = $request->building_shift_id;
        $assignment->notes = $request->notes;
        $assignment->status = $request->status;
        $assignment->assigned_by = Auth::id();
        $assignment->save();

        // Send notifications
        if (!$request->id) {
            // New assignment
            NotificationHelper2::sendNotification(
                $request->guard_user_id,
                'Gate Assignment',
                'You have been assigned to ' . $gate->name . ' during ' . $shift->name . ' shift',
                [],
                ['save_to_db' => true, 'building_id' => $building->id, 'type' => 'patrol_assignment']
            );
        } else {
            // Edit assignment
            if ($oldGuardId != $request->guard_user_id) {
                // Guard changed
                NotificationHelper2::sendNotification(
                    $oldGuardId,
                    'Assignment Removed',
                    'Your assignment at ' . $gate->name . ' has been removed',
                    [],
                    ['save_to_db' => true, 'building_id' => $building->id, 'type' => 'patrol_assignment']
                );
                NotificationHelper2::sendNotification(
                    $request->guard_user_id,
                    'Gate Assignment',
                    'You have been assigned to ' . $gate->name . ' during ' . $shift->name . ' shift',
                    [],
                    ['save_to_db' => true, 'building_id' => $building->id, 'type' => 'patrol_assignment']
                );
            } else {
                // Same guard, gate/shift changed
                NotificationHelper2::sendNotification(
                    $request->guard_user_id,
                    'Assignment Updated',
                    'Your assignment has been updated to ' . $gate->name . ' during ' . $shift->name . ' shift',
                    [],
                    ['save_to_db' => true, 'building_id' => $building->id, 'type' => 'patrol_assignment']
                );
            }
        }

        return redirect()->back()->with('success', $msg);
    }

    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        if (!($user->role == 'BA' || ($user->selectedRole && $user->selectedRole->name == 'Security'))) {
            return response()->json(['msg' => 'failed'], 403);
        }

        $building = $user->building;
        $assignment = GuardPatrolAssignment::where('id', $id)
            ->where('building_id', $building->id)
            ->with(['guardUser', 'patrolLocation', 'buildingShift'])
            ->first();

        if (!$assignment) {
            return response()->json(['msg' => 'failed'], 404);
        }

        // Notify guard before deleting
        NotificationHelper2::sendNotification(
            $assignment->guard_user_id,
            'Assignment Removed',
            'Your assignment at ' . ($assignment->gate->name ?? 'Gate') . ' during ' . ($assignment->buildingShift->name ?? 'Shift') . ' has been removed',
            [],
            ['save_to_db' => true, 'building_id' => $building->id, 'type' => 'patrol_assignment']
        );

        $assignment->delete();

        return response()->json(['msg' => 'success'], 200);
    }

    protected function getOrCreateGuardRole()
    {
        $role = Role::whereRaw("LOWER(TRIM(COALESCE(slug, ''))) = ?", ['guard'])->first();
        if ($role) {
            return $role;
        }

        $role = Role::whereRaw("LOWER(TRIM(COALESCE(slug, ''))) LIKE ?", ['%guard%'])->first();
        if ($role) {
            return $role;
        }

        try {
            $new = new Role();
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
}
