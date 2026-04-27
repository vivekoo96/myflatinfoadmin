<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatrolLocation;
use App\Models\PatrolDailyLog;
use App\Models\Building;
use App\Models\BuildingShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Carbon\Carbon;

class PatrolTaskController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // GET /api/get-patrol-tasks?date=YYYY-MM-DD
    // Guard opens app → sees all tasks for their gate on a date
    // ─────────────────────────────────────────────────────────────
    public function getPatrolTasks(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $building = null;
        $gate = $user->gate;

        // Guard: must have a gate assigned
        if ($gate && $gate->building) {
            $building = $gate->building;
        }
        // BA: use building_id from request or user context
        elseif ($request->filled('building_id')) {
            $building = Building::find($request->building_id);
            if (!$building) {
                return response()->json(['success' => false, 'message' => 'Building not found'], 404);
            }
        }

        if (!$building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first or provide building_id'], 403);
        }

        $date = $request->filled('date')
            ? Carbon::parse($request->date)->toDateString()
            : today()->toDateString();

        // All active schedule entries (patrol_locations with gate_id set)
        $scheduleQuery = PatrolLocation::where('building_id', $building->id)
            ->whereNotNull('gate_id')
            ->where('status', 'Active')
            ->whereNull('deleted_at');

        // If Guard: filter by their gate only
        if ($gate) {
            $scheduleQuery->where('gate_id', $gate->id);
        }
        // If BA: show all schedules in the building

        $schedules = $scheduleQuery->orderBy('patrol_time')->get();

        $completedTasksCount = 0;
        $isGuard = $gate ? true : false;

        $tasks = $schedules->map(function ($schedule) use ($user, $date, &$completedTasksCount, $isGuard) {
            $totalLocations  = $this->getCompletionCountLocations($schedule->building_id, $date);

            // If Guard: show their completion, If BA: show total completion by all guards
            $completedQuery = PatrolDailyLog::where('patrol_schedule_id', $schedule->id)
                ->where('patrol_date', $date);

            if ($isGuard) {
                $completedQuery->where('guard_user_id', $user->id);
            }

            $completedLocations = $completedQuery->count();

            $taskStatus = $this->calculateStatus($totalLocations, $completedLocations, $date);

            if ($taskStatus === 'Completed') {
                $completedTasksCount++;
            }

            return [
                'id'                  => $schedule->id,
                'building_id'         => $schedule->building_id,
                'patrol_time'         => $schedule->patrol_time,
                'gate_id'             => $schedule->gate_id,
                'status'              => $schedule->status,
                'deleted_at'          => $schedule->deleted_at,
                'created_at'          => $schedule->created_at,
                'updated_at'          => $schedule->updated_at,
                'completed_locations' => $completedLocations,
                'total_locations'     => $totalLocations,
                'task_status'         => $taskStatus,
            ];
        });

        $totalTasks     = $tasks->count();
        $remainingCount = $tasks->where('task_status', '!=', 'Completed')->count();

        return response()->json([
            'success'               => true,
            'date'                  => $date,
            'gate_name'             => $gate->name,
            'total_tasks'           => $totalTasks,
            'completed_tasks'       => $completedTasksCount,
            'remaining_tasks_count' => $remainingCount,
            'tasks'                 => $tasks->values(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/get-patrol-task-locations?schedule_id=X&date=YYYY-MM-DD
    // Guard views locations for a specific task on a date
    // ─────────────────────────────────────────────────────────────
    public function getTaskLocations(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|exists:patrol_locations,id',
            'date'        => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $building = null;
        $gate = $user->gate;

        // Guard: must have a gate assigned
        if ($gate && $gate->building) {
            $building = $gate->building;
        }
        // BA: use building_id from request or user context
        elseif ($request->filled('building_id')) {
            $building = Building::find($request->building_id);
            if (!$building) {
                return response()->json(['success' => false, 'message' => 'Building not found'], 404);
            }
        }

        if (!$building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first or provide building_id'], 403);
        }

        $date = $request->filled('date') ? $request->date : today()->toDateString();

        // Verify the schedule belongs to the guard's gate
        $schedule = PatrolLocation::where('id', $request->schedule_id)
            ->where('gate_id', $gate->id)
            ->where('building_id', $building->id)
            ->whereNull('deleted_at')
            ->first();

        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Schedule not found for your gate'], 404);
        }

        // Eligible locations: created BEFORE the patrol date (not on same day)
        $locations = $this->getEligibleLocations($building->id, $date);

        $completedCount = 0;
        $isGuard = $gate ? true : false;

        $data = $locations->map(function ($location) use ($schedule, $user, $date, &$completedCount, $isGuard) {
            // If Guard: check their checkin, If BA: check any guard's checkin
            $logQuery = PatrolDailyLog::where('patrol_schedule_id', $schedule->id)
                ->where('patrol_location_id', $location->id)
                ->where('patrol_date', $date);

            if ($isGuard) {
                $logQuery->where('guard_user_id', $user->id);
            }

            $log = $logQuery->first();
            $isCompleted = !is_null($log);
            if ($isCompleted) $completedCount++;

            return [
                'id'          => $location->id,
                'building_id' => $location->building_id,
                'name'        => $location->name,
                'description' => $location->description,
                'qr_string'   => $location->qr_string,
                'status'      => $location->status,
                'deleted_at'  => $location->deleted_at,
                'created_at'  => $location->created_at,
                'updated_at'  => $location->updated_at,
                'completed'   => $isCompleted,
                'checkin_time'=> $log ? $log->checked_at->format('Y-m-d H:i:s') : null,
            ];
        });

        $total   = $data->count();
        $pending = $total - $completedCount;

        return response()->json([
            'success'     => true,
            'schedule_id' => (int) $request->schedule_id,
            'date'        => $date,
            'total'       => $total,
            'completed'   => $completedCount,
            'pending'     => $pending,
            'task_status' => $this->calculateStatus($total, $completedCount, $date),
            'data'        => $data->values(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/patrol-task-checkin
    // Guard checks in at a location
    // ─────────────────────────────────────────────────────────────
    public function submitCheckin(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Only guards (with gate assigned) can check in, not BA
        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Only guards can check in. Please select a gate first'], 403);
        }

        $validator = Validator::make($request->all(), [
            'patrol_task_id'      => 'required|exists:patrol_locations,id',
            'patrol_location_id'  => 'required|exists:patrol_locations,id',
            'checkin_type'        => 'required|in:photo,qr',
            'photo'               => 'required_if:checkin_type,photo|nullable|image|mimes:jpg,jpeg,png|max:4096',
            'qr_scanned_value'    => 'required_if:checkin_type,qr|nullable|string',
            'date'                => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $date     = $request->filled('date') ? $request->date : today()->toDateString();
        $building = $gate->building;
        $gateId   = $gate->id;  // Auto-use guard's gate

        // Auto-detect current shift based on present time
        $currentTime = Carbon::now()->format('H:i');
        $currentShift = BuildingShift::where('building_id', $building->id)
            ->where('status', 'Active')
            ->whereRaw("TIME(start_time) <= ?", [$currentTime])
            ->whereRaw("TIME(end_time) >= ?", [$currentTime])
            ->first();

        if (!$currentShift) {
            return response()->json(['success' => false, 'message' => 'No active shift found for current time'], 403);
        }

        // Verify schedule belongs to guard's gate
        $schedule = PatrolLocation::where('id', $request->patrol_task_id)
            ->where('gate_id', $gateId)
            ->where('building_id', $building->id)
            ->whereNull('deleted_at')
            ->first();

        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Patrol task not found for your gate'], 404);
        }

        // Verify location is eligible (created on or before patrol date)
        $location = PatrolLocation::where('id', $request->patrol_location_id)
            ->where('building_id', $building->id)
            ->whereNull('gate_id')
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '<=', $date)
            ->first();

        if (!$location) {
            return response()->json(['success' => false, 'message' => 'Location not found or not eligible for this date'], 404);
        }

        // Verify QR code matches location
        if ($request->checkin_type === 'qr') {
            if ($location->qr_string !== $request->qr_scanned_value) {
                return response()->json(['success' => false, 'message' => 'Invalid QR code for this location'], 422);
            }
        }

        // Prevent duplicate check-in
        $existing = PatrolDailyLog::where('patrol_schedule_id', $schedule->id)
            ->where('patrol_location_id', $location->id)
            ->where('guard_user_id', $user->id)
            ->where('patrol_date', $date)
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Already checked in at this location today'], 409);
        }

        // Handle photo upload
        $photoUrl = null;
        if ($request->checkin_type === 'photo' && $request->hasFile('photo')) {
            if (!file_exists(public_path('/images/patrols/'))) {
                mkdir(public_path('/images/patrols/'), 0755, true);
            }
            $file     = $request->file('photo');
            $filename = 'patrols/' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('/images/patrols/'), $filename);
            $photoUrl = $filename;
        }

        // Save check-in
        PatrolDailyLog::create([
            'building_id'         => $building->id,
            'patrol_schedule_id'  => $schedule->id,
            'patrol_location_id'  => $location->id,
            'guard_user_id'       => $user->id,
            'patrol_date'         => $date,
            'checkin_type'        => $request->checkin_type,
            'photo_url'           => $photoUrl,
            'qr_scanned_value'    => $request->checkin_type === 'qr' ? $request->qr_scanned_value : null,
            'checked_at'          => now(),
        ]);

        // Calculate updated progress
        $totalLocations     = $this->getCompletionCountLocations($building->id, $date);
        $completedLocations = PatrolDailyLog::where('patrol_schedule_id', $schedule->id)
            ->where('guard_user_id', $user->id)
            ->where('patrol_date', $date)
            ->count();

        $taskStatus = $this->calculateStatus($totalLocations, $completedLocations, $date);

        return response()->json([
            'success'                  => true,
            'message'                  => 'Check-in recorded successfully',
            'patrol_task_id'           => $schedule->id,
            'patrol_location_id'       => $location->id,
            'location'                 => $location->name,
            'checked_at'               => now()->format('Y-m-d H:i:s'),
            'no_of_location_checked'   => $completedLocations,
            'no_of_location_tochecked' => $totalLocations,
            'task_status'              => $taskStatus,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────

    // Get physical locations eligible for check-in on a given date
    // Rule: locations created on or BEFORE the patrol date (includes same day)
    private function getEligibleLocations($buildingId, $date)
    {
        return PatrolLocation::where('building_id', $buildingId)
            ->whereNull('gate_id')
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '<=', $date)
            ->orderBy('name')
            ->get();
    }

    // Get locations that count toward task completion for a given date
    // Rule: locations created BEFORE the patrol date (not on same day)
    // This prevents new locations added during patrol day from affecting completion percentage
    private function getCompletionCountLocations($buildingId, $date)
    {
        return PatrolLocation::where('building_id', $buildingId)
            ->whereNull('gate_id')
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '<=', $date)
            ->count();
    }

    // Calculate task status
    // Pending → onProgress → Completed → Missed
    private function calculateStatus($totalLocations, $completedLocations, $date)
    {
        if ($totalLocations === 0) {
            return 'Pending';
        }

        if ($completedLocations >= $totalLocations) {
            return 'Completed';
        }

        if ($completedLocations > 0) {
            return 'onProgress';
        }

        // No check-ins yet — if date is in the past → Missed
        if ($date < today()->toDateString()) {
            return 'Missed';
        }

        return 'Pending';
    }
}
