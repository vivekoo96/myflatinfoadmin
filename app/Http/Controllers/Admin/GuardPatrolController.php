<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuardPatrol;
use App\Models\PatrolLocation;
use App\Models\BuildingShift;
use App\Models\Guard;
use App\Models\BuildingUser;
use App\Models\Role;
use Illuminate\Http\Request;
use Auth;

class GuardPatrolController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!($user->role == 'BA' || ($user->selectedRole && $user->selectedRole->name == 'Security'))) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $user->building;

        // Build guards list using same pattern as SecurityNoteController
        $guardRole = $this->getOrCreateGuardRole();
        $guards = [];
        if ($guardRole) {
            $buildingUsers = BuildingUser::where('building_id', $building->id)
                ->where('role_id', $guardRole->id)
                ->with('user')
                ->get();
            $guards = $buildingUsers->pluck('user', 'user_id');
        }

        $shifts = BuildingShift::where('building_id', $building->id)->get();

        // Only show physical locations (gate_id = null) in filter, not schedules
        $locations = PatrolLocation::where('building_id', $building->id)
            ->whereNull('gate_id')
            ->whereNull('deleted_at')
            ->with('gate')
            ->get()
            ->map(function($loc) {
                $loc->display_name = $loc->name;
                return $loc;
            });

        // Query 1: General Patrols
        $generalQuery = \DB::table('guard_patrols')
            ->where('building_id', $building->id)
            ->select([
                'id',
                'guard_user_id',
                'patrol_location_id',
                'checkin_type',
                'shift',
                'photo_url',
                'checked_in_at',
                \DB::raw("'general' as log_type")
            ]);

        // Query 2: Task Logs (old patrol_task_logs table)
        $taskQuery = \DB::table('patrol_task_logs')
            ->join('patrol_locations', 'patrol_task_logs.patrol_task_id', '=', 'patrol_locations.id')
            ->leftJoin('building_shifts', 'patrol_locations.building_shift_id', '=', 'building_shifts.id')
            ->where('patrol_task_logs.building_id', $building->id)
            ->select([
                'patrol_task_logs.id',
                'patrol_task_logs.guard_user_id',
                'patrol_task_logs.patrol_location_id',
                'patrol_task_logs.checkin_type',
                'building_shifts.name as shift',
                'patrol_task_logs.photo_url',
                'patrol_task_logs.checked_at as checked_in_at',
                \DB::raw("'task' as log_type")
            ]);

        // Query 3: NEW Patrol Daily Logs (patrol_daily_logs table - date-based API)
        $dailyLogsQuery = \DB::table('patrol_daily_logs')
            ->join('patrol_locations as schedule', 'patrol_daily_logs.patrol_schedule_id', '=', 'schedule.id')
            ->leftJoin('building_shifts', 'schedule.building_shift_id', '=', 'building_shifts.id')
            ->where('patrol_daily_logs.building_id', $building->id)
            ->select([
                'patrol_daily_logs.id',
                'patrol_daily_logs.guard_user_id',
                'patrol_daily_logs.patrol_location_id',
                'patrol_daily_logs.checkin_type',
                'building_shifts.name as shift',
                'patrol_daily_logs.photo_url',
                'patrol_daily_logs.checked_at as checked_in_at',
                \DB::raw("'daily' as log_type")
            ]);

        // Apply same filters to all three queries
        if ($request->filled('guard_user_id')) {
            $generalQuery->where('guard_user_id', $request->guard_user_id);
            $taskQuery->where('patrol_task_logs.guard_user_id', $request->guard_user_id);
            $dailyLogsQuery->where('patrol_daily_logs.guard_user_id', $request->guard_user_id);
        }
        if ($request->filled('patrol_location_id')) {
            $generalQuery->where('patrol_location_id', $request->patrol_location_id);
            $taskQuery->where('patrol_task_logs.patrol_location_id', $request->patrol_location_id);
            $dailyLogsQuery->where('patrol_daily_logs.patrol_location_id', $request->patrol_location_id);
        }
        if ($request->filled('shift')) {
            $generalQuery->where('shift', $request->shift);
            $taskQuery->where('building_shifts.name', $request->shift);
            $dailyLogsQuery->where('building_shifts.name', $request->shift);
        }
        if ($request->filled('checkin_type')) {
            $generalQuery->where('checkin_type', $request->checkin_type);
            $taskQuery->where('patrol_task_logs.checkin_type', $request->checkin_type);
            $dailyLogsQuery->where('patrol_daily_logs.checkin_type', $request->checkin_type);
        }
        if ($request->filled('date')) {
            $generalQuery->whereDate('checked_in_at', $request->date);
            $taskQuery->whereDate('patrol_task_logs.checked_at', $request->date);
            $dailyLogsQuery->whereDate('patrol_daily_logs.checked_at', $request->date);
        }

        // Combine all three sources and paginate
        // unionAll combines results from all three queries
        $combinedResults = $generalQuery->unionAll($taskQuery)->unionAll($dailyLogsQuery)
            ->orderBy('checked_in_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // Transform results to include relations for the view
        $patrols = $combinedResults->getCollection()->map(function($item) {
            $item->guardUser = \App\Models\User::find($item->guard_user_id);
            $item->patrolLocation = \App\Models\PatrolLocation::find($item->patrol_location_id);
            if ($item->checked_in_at) {
                $item->checked_in_at = \Carbon\Carbon::parse($item->checked_in_at);
            }
            return $item;
        });
        $combinedResults->setCollection($patrols);
        $patrols = $combinedResults;

        $filters = array_merge(
            ['guard_user_id' => '', 'patrol_location_id' => '', 'shift' => '', 'checkin_type' => '', 'date' => ''],
            $request->only(['guard_user_id', 'patrol_location_id', 'shift', 'checkin_type', 'date'])
        );

        return view('admin.guard_patrols.index', compact('patrols', 'guards', 'locations', 'filters', 'shifts'));
    }

    public function checkin()
    {
        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return redirect('permission-denied');
        }

        $locations = PatrolLocation::where('building_id', $building->id)
            ->where('status', 'Active')
            ->get();

        $shifts = BuildingShift::where('building_id', $building->id)
            ->where('status', 'Active')
            ->orderBy('start_time')
            ->get();

        return view('admin.guard_patrols.checkin', compact('locations', 'shifts', 'building'));
    }

    public function submitCheckin(Request $request)
    {
        $rules = [
            'patrol_location_id' => 'required|exists:patrol_locations,id',
            'checkin_type' => 'required|in:photo,qr',
            'building_shift_id' => 'required|exists:building_shifts,id',
            'photo' => 'required_if:checkin_type,photo|nullable|image|mimes:jpg,jpeg,png|max:4096',
            'qr_scanned_value' => 'required_if:checkin_type,qr|nullable|string',
        ];

        $validation = \Validator::make($request->all(), $rules);

        if ($validation->fails()) {
            return redirect()->back()->with('error', $validation->errors()->first());
        }

        $user = Auth::user();
        $building = $user->building;

        // Ensure location belongs to this building
        $location = PatrolLocation::where('id', $request->patrol_location_id)
            ->where('building_id', $building->id)
            ->firstOrFail();

        $photo_url = null;
        if ($request->checkin_type === 'photo' && $request->hasFile('photo')) {
            if (!file_exists(public_path('/images/patrols/'))) {
                mkdir(public_path('/images/patrols/'), 0755, true);
            }

            $file = $request->file('photo');
            $ext = $file->getClientOriginalExtension();
            $filename = 'patrols/' . uniqid() . '.' . $ext;
            $file->move(public_path('/images/patrols/'), $filename);
            $photo_url = $filename;
        }

        if ($request->checkin_type === 'qr') {
            // Verify QR code matches the location
            if ($location->qr_string !== $request->qr_scanned_value) {
                return redirect()->back()->with('error', 'Invalid QR code for the selected location.');
            }
        }

        GuardPatrol::create([
            'building_id' => $building->id,
            'guard_user_id' => $user->id,
            'patrol_location_id' => $request->patrol_location_id,
            'checkin_type' => $request->checkin_type,
            'shift' => $request->shift,
            'photo_url' => $photo_url,
            'qr_scanned_value' => $request->checkin_type === 'qr' ? $request->qr_scanned_value : null,
            'checked_in_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Check-in recorded successfully!');
    }

    public function resolveQr(Request $request)
    {
        $location = PatrolLocation::where('qr_string', $request->qr_string)
            ->where('building_id', Auth::user()->building_id)
            ->where('status', 'Active')
            ->first();

        if ($location) {
            return response()->json(['success' => true, 'id' => $location->id, 'name' => $location->name]);
        }

        return response()->json(['success' => false, 'message' => 'Location not found']);
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
