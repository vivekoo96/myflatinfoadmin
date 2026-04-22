<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardPatrol;
use App\Models\PatrolLocation;
use App\Models\BuildingShift;
use Illuminate\Http\Request;
use Auth;

class GuardPatrolController extends Controller
{
    public function getLocations(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first using select-gate endpoint'], 403);
        }

        $building = $gate->building;

        $locations = PatrolLocation::where('building_id', $building->id)
            ->where('status', 'Active')
            ->select('id', 'name', 'description', 'qr_string')
            ->get();

        // Get available shifts for the building
        $shifts = BuildingShift::where('building_id', $building->id)
            ->where('status', 'Active')
            ->select('id', 'name', 'start_time', 'end_time')
            ->get();

        // Detect current shift based on current time
        $now = now()->format('H:i:s');
        $currentShift = $shifts->first(function ($shift) use ($now) {
            if ($shift->start_time <= $shift->end_time) {
                return $now >= $shift->start_time && $now < $shift->end_time;
            }
            return $now >= $shift->start_time || $now < $shift->end_time;
        });

        return response()->json([
            'success' => true,
            'data' => $locations,
            'shifts' => $shifts,
            'current_shift' => $currentShift,
        ]);
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
            return response()->json([
                'success' => false,
                'message' => $validation->errors()->first(),
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first using select-gate endpoint'], 403);
        }

        $building = $gate->building;

        // Ensure location belongs to this building
        $location = PatrolLocation::where('id', $request->patrol_location_id)
            ->where('building_id', $building->id)
            ->first();

        if (!$location) {
            return response()->json(['success' => false, 'message' => 'Location not found'], 404);
        }

        // Validate shift belongs to this building
        $buildingShift = BuildingShift::where('id', $request->building_shift_id)
            ->where('building_id', $building->id)
            ->where('status', 'Active')
            ->first();

        if (!$buildingShift) {
            return response()->json(['success' => false, 'message' => 'Shift not found or not active for this building'], 404);
        }

        // Check if current time is at or after patrol_time
        $currentTime = now()->format('H:i:s');
        if ($currentTime < $location->patrol_time) {
            return response()->json([
                'success' => false,
                'message' => 'Patrol cannot be started before scheduled time. Start time: ' . $location->patrol_time,
            ], 403);
        }

        // Prevent duplicate check-ins for same location today
        $existingCheckin = GuardPatrol::where('guard_user_id', $user->id)
            ->where('patrol_location_id', $location->id)
            ->whereDate('checked_in_at', today())
            ->first();

        if ($existingCheckin) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in at this location today.',
            ], 409);
        }

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
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code for the selected location.',
                ], 422);
            }
        }

        try {
            $patrol = GuardPatrol::create([
                'building_id' => $building->id,
                'guard_user_id' => $user->id,
                'patrol_location_id' => $request->patrol_location_id,
                'checkin_type' => $request->checkin_type,
                'shift' => $buildingShift->name,
                'building_shift_id' => $buildingShift->id,
                'photo_url' => $photo_url,
                'qr_scanned_value' => $request->checkin_type === 'qr' ? $request->qr_scanned_value : null,
                'checked_in_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-in recorded successfully!',
                'data' => [
                    'id' => $patrol->id,
                    'location' => $location->name,
                    'checked_in_at' => $patrol->checked_in_at->format('Y-m-d H:i:s'),
                    'type' => $patrol->checkin_type,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Guard patrol check-in failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record check-in. Please try again.',
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first using select-gate endpoint'], 403);
        }

        $building = $gate->building;

        $query = GuardPatrol::where('building_id', $building->id)
            ->where('guard_user_id', $user->id)
            ->with(['patrolLocation', 'buildingShift']);

        // Optional filters
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }
        if ($request->filled('date')) {
            $query->whereDate('checked_in_at', $request->date);
        }
        if ($request->filled('days')) {
            // Last N days
            $days = (int)$request->days;
            if ($days > 0 && $days <= 365) {
                $query->where('checked_in_at', '>=', now()->subDays($days));
            }
        }

        $patrols = $query->orderBy('checked_in_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($patrol) {
                return [
                    'id' => $patrol->id,
                    'location' => $patrol->patrolLocation ? $patrol->patrolLocation->name : 'N/A',
                    'shift' => $patrol->shift,
                    'shift_id' => $patrol->building_shift_id,
                    'shift_start' => $patrol->buildingShift ? $patrol->buildingShift->start_time : null,
                    'shift_end' => $patrol->buildingShift ? $patrol->buildingShift->end_time : null,
                    'type' => $patrol->checkin_type,
                    'checked_in_at' => $patrol->checked_in_at->format('Y-m-d H:i:s'),
                    'photo_url' => $patrol->photo_url ? asset('/public/images/' . $patrol->photo_url) : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $patrols,
            'count' => $patrols->count(),
        ]);
    }

    public function getMyAssignments(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first using select-gate endpoint'], 403);
        }

        $building = $gate->building;

        $assignments = \App\Models\GuardPatrolAssignment::where('guard_user_id', $user->id)
            ->where('building_id', $building->id)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->with(['patrolLocation', 'buildingShift'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'location' => $assignment->patrolLocation->name,
                    'location_id' => $assignment->patrol_location_id,
                    'shift' => $assignment->buildingShift->name,
                    'shift_id' => $assignment->building_shift_id,
                    'shift_start' => $assignment->buildingShift->start_time,
                    'shift_end' => $assignment->buildingShift->end_time,
                    'notes' => $assignment->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $assignments,
            'count' => $assignments->count(),
        ]);
    }

    public function getNextPatrolSchedule(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first using select-gate endpoint'], 403);
        }

        $building = $gate->building;

        // Get today's check-ins for this guard
        $todayCheckins = GuardPatrol::where('guard_user_id', $user->id)
            ->whereDate('checked_in_at', today())
            ->pluck('patrol_location_id')
            ->toArray();

        $schedules = PatrolLocation::where('gate_id', $gate->id)
            ->where('building_id', $building->id)
            ->where('status', 'Active')
            ->with('buildingShift')
            ->orderBy('patrol_time')
            ->get()
            ->map(function ($location) use ($gate, $todayCheckins, $user) {
                $lastCheckIn = GuardPatrol::where('guard_user_id', $user->id)
                    ->where('patrol_location_id', $location->id)
                    ->whereDate('checked_in_at', today())
                    ->latest('checked_in_at')
                    ->first();

                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'gate_name' => $gate->name,
                    'gate_id' => $gate->id,
                    'shift_name' => $location->buildingShift ? $location->buildingShift->name : 'N/A',
                    'shift_id' => $location->building_shift_id,
                    'shift_start' => $location->buildingShift ? $location->buildingShift->start_time : null,
                    'shift_end' => $location->buildingShift ? $location->buildingShift->end_time : null,
                    'patrol_time' => $location->patrol_time,
                    'qr_string' => $location->qr_string,
                    'is_completed' => in_array($location->id, $todayCheckins),
                    'last_checked_at' => $lastCheckIn ? $lastCheckIn->checked_in_at->format('Y-m-d H:i:s') : null,
                ];
            });

        $completed = collect($schedules)->where('is_completed', true)->count();

        return response()->json([
            'success' => true,
            'gate_name' => $gate->name,
            'total' => $schedules->count(),
            'completed' => $completed,
            'data' => $schedules,
        ]);
    }

    public function getNextPatrolPoint(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first using select-gate endpoint'], 403);
        }

        $building = $gate->building;

        // Get all active patrol locations for this gate, ordered by patrol_time
        $allLocations = PatrolLocation::where('gate_id', $gate->id)
            ->where('building_id', $building->id)
            ->where('status', 'Active')
            ->with('buildingShift')
            ->orderBy('patrol_time')
            ->get();

        // Get today's check-ins for locations in this gate only
        $checkedLocationIds = [];

        // Check old GuardPatrol system - check ALL records for this guard today
        $allGuardPatrols = GuardPatrol::where('guard_user_id', $user->id)
            ->whereDate('checked_in_at', today())
            ->pluck('patrol_location_id')
            ->unique()
            ->toArray();

        // Filter to only locations in this gate
        $guardPatrolCheckins = array_intersect($allGuardPatrols, $allLocations->pluck('id')->toArray());
        $checkedLocationIds = array_merge($checkedLocationIds, $guardPatrolCheckins);

        // Check new PatrolTaskLog system - get patrol tasks this guard checked in for today
        $completedTaskIds = \App\Models\PatrolTaskLog::where('guard_user_id', $user->id)
            ->whereDate('checked_at', today())
            ->distinct('patrol_task_id')
            ->pluck('patrol_task_id')
            ->toArray();

        // Map patrol task IDs to actual patrol task location IDs in this gate
        $completedPatrolTaskLocationIds = PatrolLocation::whereIn('id', $completedTaskIds)
            ->where('gate_id', $gate->id)
            ->pluck('id')
            ->toArray();

        $checkedLocationIds = array_merge($checkedLocationIds, $completedPatrolTaskLocationIds);

        $todayCheckins = array_unique($checkedLocationIds);

        $nextLocation = null;
        $remaining = [];

        foreach ($allLocations as $location) {
            $isCompleted = in_array($location->id, $todayCheckins);

            $locationData = [
                'id' => $location->id,
                'name' => $location->name,
                'patrol_time' => $location->patrol_time,
                'shift_name' => $location->buildingShift ? $location->buildingShift->name : 'N/A',
                'shift_start' => $location->buildingShift ? $location->buildingShift->start_time : null,
                'shift_end' => $location->buildingShift ? $location->buildingShift->end_time : null,
                'qr_string' => $location->qr_string,
                'is_completed' => $isCompleted,
            ];

            // If not completed and this is the first uncompleted, set as next
            if (!$isCompleted && !$nextLocation) {
                $nextLocation = $locationData;
            }

            // Add all uncompleted to remaining list
            if (!$isCompleted) {
                $remaining[] = $locationData;
            }
        }

        $completed = count($todayCheckins);
        $total = $allLocations->count();

        // Debug info if no check-ins found
        $debugInfo = null;
        if ($completed === 0 && $total > 0) {
            $debugInfo = [
                'all_guard_patrols_today' => $allGuardPatrols,
                'all_task_logs_today' => $allTaskLogs,
                'gate_location_ids' => $allLocations->pluck('id')->toArray(),
                'guard_id' => $user->id,
                'gate_id' => $gate->id,
            ];
        }

        return response()->json([
            'success' => true,
            'gate_name' => $gate->name,
            'completed' => $completed,
            'total' => $total,
            'progress' => "$completed/$total",
            'next_patrol_point' => $nextLocation,
            'remaining_patrols' => $remaining,
            'debug' => $debugInfo,
        ]);
    }

    public function getGateShiftProgress(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validation = \Validator::make($request->all(), [
            'gate_id' => 'required|exists:gates,id',
            'building_shift_id' => 'required|exists:building_shifts,id',
        ]);

        if ($validation->fails()) {
            return response()->json(['success' => false, 'message' => $validation->errors()->first()], 422);
        }

        // Get building from authenticated user (BA/Security context)
        $building = null;
        if ($user->building) {
            $building = $user->building;
        } elseif ($user->gate && $user->gate->building) {
            $building = $user->gate->building;
        }

        if (!$building) {
            return response()->json(['success' => false, 'message' => 'Building context not found. Please select a gate or ensure you have building access'], 403);
        }

        $gate = \App\Models\Gate::where('id', $request->gate_id)
            ->where('building_id', $building->id)
            ->first();

        if (!$gate) {
            return response()->json(['success' => false, 'message' => 'Gate not found for this building'], 404);
        }

        $shift = BuildingShift::where('id', $request->building_shift_id)
            ->where('building_id', $building->id)
            ->first();

        if (!$shift) {
            return response()->json(['success' => false, 'message' => 'Shift not found for this building'], 404);
        }

        $locations = PatrolLocation::where('gate_id', $gate->id)
            ->where('building_shift_id', $shift->id)
            ->where('status', 'Active')
            ->orderBy('patrol_time')
            ->get();

        $data = $locations->map(function ($location) {
            $patrols = GuardPatrol::where('patrol_location_id', $location->id)
                ->whereDate('checked_in_at', today())
                ->with('guardUser')
                ->orderBy('checked_in_at', 'desc')
                ->get()
                ->map(function ($patrol) {
                    return [
                        'checkin_type' => $patrol->checkin_type,
                        'checked_at' => $patrol->checked_in_at->format('Y-m-d H:i:s'),
                        'checked_by' => $patrol->guardUser->name ?? 'Guard',
                    ];
                });

            return [
                'id' => $location->id,
                'name' => $location->name,
                'patrol_time' => $location->patrol_time,
                'is_completed' => $patrols->count() > 0,
                'patrols' => $patrols,
            ];
        });

        $completed = $data->where('is_completed', true)->count();
        $total = $data->count();

        return response()->json([
            'success' => true,
            'gate_name' => $gate->name,
            'shift_name' => $shift->name . ' (' . $shift->start_time . ' - ' . $shift->end_time . ')',
            'date' => today()->toDateString(),
            'total' => $total,
            'completed' => $completed,
            'remaining' => $total - $completed,
            'progress' => "$completed/$total",
            'data' => $data,
        ]);
    }

    public function getGuardPatrolProgress(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validation = \Validator::make($request->all(), [
            'building_shift_id' => 'required|exists:building_shifts,id',
            'date'              => 'nullable|date_format:Y-m-d',
        ]);

        if ($validation->fails()) {
            return response()->json(['success' => false, 'message' => $validation->errors()->first()], 422);
        }

        // Get building and guard from authenticated user
        $building = null;
        if ($user->building) {
            $building = $user->building;
        } elseif ($user->gate && $user->gate->building) {
            $building = $user->gate->building;
        }

        if (!$building) {
            return response()->json(['success' => false, 'message' => 'Building context not found. Please select a gate or ensure you have building access'], 403);
        }

        $date  = $request->date ? \Carbon\Carbon::parse($request->date) : today();
        $guard = $user; // Use authenticated user as the guard

        $shift = BuildingShift::where('id', $request->building_shift_id)
            ->where('building_id', $building->id)
            ->first();

        if (!$shift) {
            return response()->json(['success' => false, 'message' => 'Shift not found for this building'], 404);
        }

        $assignment = \App\Models\GuardPatrolAssignment::where('guard_user_id', $guard->id)
            ->where('building_shift_id', $shift->id)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->first();

        if (!$assignment || !$assignment->gate_id) {
            return response()->json(['success' => false, 'message' => 'Guard is not assigned to any gate for this shift'], 404);
        }

        $gate = \App\Models\Gate::find($assignment->gate_id);

        $locations = PatrolLocation::where('gate_id', $gate->id)
            ->where('building_shift_id', $shift->id)
            ->where('status', 'Active')
            ->orderBy('patrol_time')
            ->get();

        $data = $locations->map(function ($location) use ($guard, $date) {
            $patrols = GuardPatrol::where('guard_user_id', $guard->id)
                ->where('patrol_location_id', $location->id)
                ->whereDate('checked_in_at', $date)
                ->orderBy('checked_in_at', 'desc')
                ->get()
                ->map(function ($patrol) {
                    return [
                        'checkin_type' => $patrol->checkin_type,
                        'checked_at' => $patrol->checked_in_at->format('Y-m-d H:i:s'),
                        'checked_by' => $patrol->guardUser->name ?? 'Guard',
                    ];
                });

            return [
                'id'           => $location->id,
                'name'         => $location->name,
                'patrol_time'  => $location->patrol_time,
                'is_completed' => $patrols->count() > 0,
                'patrols'      => $patrols,
            ];
        });

        $completed = $data->where('is_completed', true)->count();
        $total     = $data->count();

        return response()->json([
            'success'          => true,
            'guard_name'       => $guard->name,
            'gate_name'        => $gate->name,
            'shift_name'       => $shift->name . ' (' . $shift->start_time . ' - ' . $shift->end_time . ')',
            'date'             => $date->toDateString(),
            'total'            => $total,
            'completed'        => $completed,
            'remaining'        => $total - $completed,
            'progress'         => "$completed/$total",
            'is_shift_complete'=> $total > 0 && $completed === $total,
            'data'             => $data,
        ]);
    }

    public function submitTaskLocationCheckin(Request $request)
    {
        $rules = [
            'patrol_task_id' => 'required|exists:patrol_locations,id',
            'patrol_location_id' => 'required|exists:patrol_locations,id',
            'checkin_type' => 'required|in:photo,qr',
            'photo' => 'required_if:checkin_type,photo|nullable|image|mimes:jpg,jpeg,png|max:4096',
            'qr_scanned_value' => 'required_if:checkin_type,qr|nullable|string',
        ];

        $validation = \Validator::make($request->all(), $rules);

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validation->errors()->first(),
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Get building context
        $building = null;
        if ($user->building) {
            $building = $user->building;
        } elseif ($user->gate && $user->gate->building) {
            $building = $user->gate->building;
        }

        if (!$building) {
            return response()->json(['success' => false, 'message' => 'Building context not found'], 403);
        }

        // Verify patrol_task_id is a task (has gate_id)
        $task = PatrolLocation::where('id', $request->patrol_task_id)
            ->where('building_id', $building->id)
            ->whereNotNull('gate_id')
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Patrol task not found'], 404);
        }

        // Verify patrol_location_id is a physical location (no gate_id)
        $location = PatrolLocation::where('id', $request->patrol_location_id)
            ->where('building_id', $building->id)
            ->whereNull('gate_id')
            ->first();

        if (!$location) {
            return response()->json(['success' => false, 'message' => 'Patrol location not found'], 404);
        }

        // Prevent duplicate check-ins
        $existing = \App\Models\PatrolTaskLog::where('patrol_task_id', $task->id)
            ->where('patrol_location_id', $location->id)
            ->where('guard_user_id', $user->id)
            ->whereDate('checked_at', today())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in at this location for this task today.',
            ], 409);
        }

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
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code for the selected location.',
                ], 422);
            }
        }

        try {
            $log = \App\Models\PatrolTaskLog::create([
                'building_id' => $building->id,
                'patrol_task_id' => $task->id,
                'patrol_location_id' => $location->id,
                'guard_user_id' => $user->id,
                'checkin_type' => $request->checkin_type,
                'photo_url' => $photo_url,
                'qr_scanned_value' => $request->checkin_type === 'qr' ? $request->qr_scanned_value : null,
                'checked_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-in recorded successfully!',
                'data' => [
                    'id' => $log->id,
                    'location' => $location->name,
                    'checked_at' => $log->checked_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Task location check-in failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record check-in. Please try again.',
            ], 500);
        }
    }

    /**
     * Get patrol task progress - shows all locations for a task and completion status
     * Query: patrol_task_id (required) - The patrol task ID (scheduled task with gate_id)
     *
     * Response Structure:
     * {
     *   "success": true,                      // Request successful
     *   "task_name": "Morning Patrol",        // Name of the patrol task
     *   "patrol_time": "06:00",               // Scheduled patrol time
     *   "total": 5,                           // Total number of locations in this task
     *   "completed": 3,                       // Number of locations already checked in
     *   "remaining": 2,                       // Number of locations still pending
     *   "is_task_complete": false,            // true if all locations completed, false if pending
     *   "locations": [                        // Array of all patrol locations for this task
     *     {
     *       "id": 3,                          // Location ID
     *       "name": "Location Point A",       // Location name
     *       "is_completed": false,            // true if checked in today, false if pending
     *       "check_ins": [                    // Array of check-in records for this location today
     *         {
     *           "checkin_type": "qr|photo",  // Type of check-in (qr code or photo)
     *           "checked_at": "2026-04-22 06:15:00"  // When this location was checked in
     *         }
     *       ]
     *     }
     *   ]
     * }
     */
    public function getTaskProgress(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validation = \Validator::make($request->all(), [
            'patrol_task_id' => 'required|exists:patrol_locations,id',
        ]);

        if ($validation->fails()) {
            return response()->json(['success' => false, 'message' => $validation->errors()->first()], 422);
        }

        // Get building context
        $building = null;
        if ($user->building) {
            $building = $user->building;
        } elseif ($user->gate && $user->gate->building) {
            $building = $user->gate->building;
        }

        if (!$building) {
            return response()->json(['success' => false, 'message' => 'Building context not found'], 403);
        }

        // Find the task
        $task = PatrolLocation::where('id', $request->patrol_task_id)
            ->where('building_id', $building->id)
            ->whereNotNull('gate_id')
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Patrol task not found'], 404);
        }

        // Get all physical locations (no gate_id)
        $locations = PatrolLocation::where('building_id', $building->id)
            ->whereNull('gate_id')
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $data = $locations->map(function ($location) use ($user, $task) {
            $logs = \App\Models\PatrolTaskLog::where('patrol_task_id', $task->id)
                ->where('patrol_location_id', $location->id)
                ->where('guard_user_id', $user->id)
                ->whereDate('checked_at', today())
                ->orderBy('checked_at', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'checkin_type' => $log->checkin_type,
                        'checked_at' => $log->checked_at->format('Y-m-d H:i:s'),
                    ];
                });

            return [
                'id' => $location->id,
                'name' => $location->name,
                'description' => $location->description,
                'is_completed' => $logs->count() > 0,
                'check_ins' => $logs,
            ];
        });

        $completed = $data->where('is_completed', true)->count();
        $total = $data->count();

        return response()->json([
            'success' => true,
            'task_name' => $task->name,
            'patrol_time' => $task->patrol_time,
            'total' => $total,
            'completed' => $completed,
            'remaining' => $total - $completed,
            'is_task_complete' => $total > 0 && $completed === $total,
            'locations' => $data,
        ]);
    }
}
