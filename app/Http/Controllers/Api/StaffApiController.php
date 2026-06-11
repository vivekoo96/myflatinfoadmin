<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Flat;
use App\Models\StaffTag;
use App\Models\StaffAttendance;
use App\Models\StaffFlatAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\AuthHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class StaffApiController extends Controller
{
    // --- Flat User Endpoints ---

    public function getFlatEmps(Request $request)
    {
        $flat = AuthHelper::flat();
        if (!$flat) return response()->json(['error' => 'Flat not found'], 404);

        $query = Staff::whereHas('tags', function($q) use ($flat) {
            $q->where('flat_id', $flat->id)->where('status', 'Active');
        })->with(['attendanceLogs' => function($q) {
            $q->where('date', date('Y-m-d'));
        }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('type', 'LIKE', "%{$search}%")
                  ->orWhere('staff_id', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 20);
        $staffs = $query->paginate($perPage);

        $staffs->getCollection()->transform(function ($staff) {
            if ($staff->photo && !str_starts_with($staff->photo, 'http')) {
                $path = preg_replace('#^/?public/#', '', $staff->photo);
                $staff->photo = asset('public/' . ltrim($path, '/'));
            }
            return $staff;
        });

        return response()->json($staffs, 200);
    }

    public function getBuildingDomesticStaff(Request $request)
    {
        $flat = AuthHelper::flat();
        if (!$flat) return response()->json(['error' => 'Flat not found'], 404);

        $query = Staff::where('building_id', $flat->building_id)
            ->where('category', 'flat_staff')
            ->where('approval_status', 'Approved')
            ->where('status', 'Active')
            ->where('is_open_to_all', 1)
            ->where('type', '!=', 'Security Guard')
            ->with(['tags.flat.block']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('type', 'LIKE', "%{$search}%")
                  ->orWhere('staff_id', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 20);
        $staffs = $query->paginate($perPage);

        // Fetch all flats for the building to use when is_open_to_all is true
        $buildingFlats = \App\Models\Flat::where('building_id', $flat->building_id)
            ->with('block')
            ->get();

        $staffs->getCollection()->transform(function ($staff) use ($flat, $buildingFlats) {
            // is_added should always be based on the current flat user's tags
            $staff->is_added = $staff->tags->contains('flat_id', $flat->id);

            if ($staff->is_open_to_all) {
                $staff->assigned_flats = $buildingFlats;
            } else {
                $staff->assigned_flats = $staff->tags->map(function ($tag) {
                    return $tag->flat;
                })->filter()->values();
            }
            return $staff;
        });

        return response()->json($staffs, 200);
    }

    public function tagFlatEmp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staffs,id',
            'engagement_type' => 'required|in:In-house,Timely-basis',
            'time_slot' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $flat = AuthHelper::flat();
        if (!$flat) return response()->json(['error' => 'Flat not found'], 404);

        $staff = Staff::where('id', $request->staff_id)
                      ->where('building_id', $flat->building_id)
                      ->first();

        if (!$staff) {
            return response()->json(['error' => 'Staff member not found in this building.'], 404);
        }

        StaffTag::updateOrCreate(
            [
                'staff_id' => $staff->id,
                'flat_id' => $flat->id
            ],
            [
                'building_id' => $flat->building_id,
                'engagement_type' => $request->engagement_type,
                'time_slot' => $request->time_slot,
                'status' => 'Active'
            ]
        );

        return response()->json(['success' => true, 'message' => 'Staff assigned to your flat successfully.'], 200);
    }

    public function getStaffTypes(Request $request)
    {
        $flat = AuthHelper::flat();
        if (!$flat) return response()->json(['error' => 'Flat not found'], 404);

        $types = \App\Models\StaffType::where(function($query) use ($flat) {
            $query->where('building_id', $flat->building_id)
                  ->orWhereNull('building_id');
        })
        ->orderBy('name')
        ->get(['id', 'name']);

        return response()->json(['staff_types' => $types], 200);
    }

    public function createStaffType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $flat = AuthHelper::flat();
        if (!$flat) return response()->json(['error' => 'Flat not found'], 404);

        $type = \App\Models\StaffType::firstOrCreate([
            'building_id' => $flat->building_id,
            'name' => trim($request->name)
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Staff type created successfully',
            'type' => $type
        ], 200);
    }

    public function addFlatEmp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string',
            'type' => 'required|string',
            'address' => 'nullable|string',
            'engagement_type' => 'nullable|in:In-house,Timely-basis',
            'time_slot' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'document' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'noc' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) return response()->json(['error' => $validator->errors()->first()], 422);

        $flat = AuthHelper::flat();
        
        $staff = new Staff();
        $staff->name = $request->name;
        $staff->phone = $request->phone;
        $staff->type = $request->type;
        $staff->address = $request->address;
        $staff->category = 'flat_staff';
        $staff->building_id = $flat->building_id;
        $staff->staff_id = $this->generateUniqueStaffId();
        $staff->creator_id = Auth::id();
        $staff->creator_type = 'flat_user';
        $staff->approval_status = 'Pending';
        
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_photo.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/staff'), $filename);
            $staff->photo = 'uploads/staff/' . $filename;
        }

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $filename = time() . '_doc.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/staff/documents'), $filename);
            $staff->document_verification = 'uploads/staff/documents/' . $filename;
        }

        if ($request->hasFile('noc')) {
            $file = $request->file('noc');
            $filename = time() . '_noc.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/staff/noc'), $filename);
            $staff->noc_police = 'uploads/staff/noc/' . $filename;
        }
        
        $staff->save();

        // Tag to flat
        StaffTag::create([
            'staff_id' => $staff->id,
            'flat_id' => $flat->id,
            'building_id' => $flat->building_id,
            'engagement_type' => $request->engagement_type,
            'time_slot' => $request->time_slot,
        ]);

        return response()->json(['msg' => 'Staff added successfully', 'staff_id' => $staff->staff_id], 200);
    }

    public function markStaffPresent(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staffs,id',
        ]);

        $flat = AuthHelper::flat();
        $today = date('Y-m-d');

        // Find or create today's society entry
        $attendanceLog = StaffAttendance::firstOrCreate(
            ['staff_id' => $request->staff_id, 'date' => $today],
            ['building_id' => $flat->building_id, 'status' => 'Present', 'marked_by' => Auth::id(), 'source' => 'flat']
        );

        StaffFlatAttendance::create([
            'attendance_log_id' => $attendanceLog->id,
            'staff_id' => $request->staff_id,
            'flat_id' => $flat->id,
            'marked_at' => now(),
        ]);

        return response()->json(['msg' => 'Attendance marked present for your flat'], 200);
    }

    public function markDepartmentStaffPresent(Request $request)
    {
        $request->validate([
            'staff_id' => 'required', // Can be database ID or 6-digit staff_id_code
            'status' => 'required|in:Present,Absent,On Leave',
            'date' => 'nullable|date',
        ]);

        $staff = Staff::where('id', $request->staff_id)
            ->orWhere('staff_id', $request->staff_id)
            ->first();

        if (!$staff) {
            return response()->json(['error' => 'Staff not found'], 404);
        }

        $date = $request->date ?? date('Y-m-d');

        $attendance = StaffAttendance::updateOrCreate(
            [
                'staff_id' => $staff->id,
                'date' => $date,
            ],
            [
                'building_id' => $staff->building_id,
                'status' => $request->status,
                'marked_by' => Auth::id(),
                'source' => 'department',
            ]
        );

        return response()->json([
            'success' => true, 
            'message' => 'Attendance marked successfully',
            'staff' => [
                'name' => $staff->name,
                'staff_id' => $staff->staff_id,
                'status' => $attendance->status
            ]
        ], 200);
    }

    public function getStaffAttendanceHistory(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staffs,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
        ]);

        $flat = AuthHelper::flat();
        
        $history = StaffFlatAttendance::where('staff_id', $request->staff_id)
            ->where('flat_id', $flat->id)
            ->whereMonth('marked_at', $request->month)
            ->whereYear('marked_at', $request->year)
            ->get();

        return response()->json(['history' => $history], 200);
    }


    public function verifyStaffCode(Request $request)
    {
        $request->validate([
            'staff_id_code' => 'required|string',
        ]);

        $staff = Staff::where('staff_id', $request->staff_id_code)->first();
        if (!$staff) return response()->json(['error' => 'Invalid Staff ID'], 404);

        $today = date('Y-m-d');

        // Today's gate-level log
        $gateLog = StaffAttendance::where('staff_id', $staff->id)
            ->where('date', $today)
            ->first();

        $gateStatus = 'not_checked_in';
        $gateEntryTime = null;
        $gateExitTime = null;

        if ($gateLog) {
            $gateEntryTime = $gateLog->entry_time
                ? Carbon::parse($gateLog->entry_time)->format('h:i A') : null;
            $gateExitTime = $gateLog->exit_time
                ? Carbon::parse($gateLog->exit_time)->format('h:i A') : null;

            if ($gateLog->entry_time && !$gateLog->exit_time) {
                $gateStatus = 'checked_in';
            } elseif ($gateLog->exit_time) {
                $gateStatus = 'checked_out';
            }
        }

        // All active flat assignments for this staff
        $tags = $staff->tags()->where('status', 'Active')
            ->with('flat.block')
            ->get();

        $assignedFlats = $tags->map(function ($tag) {
            $flat = $tag->flat;
            if (!$flat) return null;

            return [
                'id'              => $flat->id,
                'flat_number'     => $flat->flat_number ?? $flat->name ?? $flat->id,
                'block'           => optional($flat->block)->name,
                'engagement_type' => $tag->engagement_type,
                'time_slot'       => $tag->time_slot,
            ];
        })->filter()->values();


        // Build safe staff response with photo URL
        $staffData = $staff->toArray();
        $staffData['photo_url'] = $staff->photo_url;

        return response()->json([
            'staff'           => $staffData,
            'gate_status'     => $gateStatus,
            'gate_entry_time' => $gateEntryTime,
            'gate_exit_time'  => $gateExitTime,
            'assigned_flats'  => $assignedFlats,
        ], 200);
    }

    /**
     * Smart gate punch — single endpoint for check-in AND check-out.
     * POST /api/gate-staff-punch
     *
     * Auto-detects what to do based on today's log:
     *   - No log today              → CHECK IN  (first entry)
     *   - Checked in, not out yet   → CHECK OUT (exit)
     *   - Checked out already       → CHECK IN  (re-entry, clears exit)
     *
     * Params: staff_id_code (6-digit required), gate_id (optional)
     */
    public function gateStaffPunch(Request $request)
    {
        $request->validate([
            'staff_id_code' => 'required|string',
            'gate_id'       => 'nullable',
        ]);

        $staff = Staff::where('staff_id', $request->staff_id_code)->first();
        if (!$staff) return response()->json(['error' => 'Invalid Staff ID'], 404);

        $today = date('Y-m-d');

        $log = StaffAttendance::firstOrNew(
            ['staff_id' => $staff->id, 'date' => $today]
        );

        // Determine action automatically
        if (!$log->exists || !$log->entry_time) {
            // No entry today → Check In
            $action = 'checked_in';
            $log->building_id = $staff->building_id;
            $log->source      = 'gate';
            $log->marked_by   = Auth::id();
            $log->entry_time  = now();
            $log->exit_time   = null;
            $log->status      = 'Present';
            if ($request->filled('gate_id')) {
                $log->gate_id = $request->gate_id;
            }
            $message = 'Staff checked in at gate successfully.';
            $timeKey = 'entry_time';
            $timeVal = Carbon::parse($log->entry_time)->format('h:i A');

        } elseif ($log->entry_time && !$log->exit_time) {
            // Inside → Check Out
            $action = 'checked_out';
            $log->exit_time = now();
            if ($request->filled('gate_id')) {
                $log->gate_id = $request->gate_id;
            }
            $message = 'Staff checked out from gate successfully.';
            $timeKey = 'exit_time';
            $timeVal = Carbon::parse($log->exit_time)->format('h:i A');

        } else {
            // Already checked out → Re-entry (new visit)
            $action = 'checked_in';
            $log->building_id = $staff->building_id;
            $log->source      = 'gate';
            $log->marked_by   = Auth::id();
            $log->entry_time  = now();
            $log->exit_time   = null;
            $log->status      = 'Present';
            if ($request->filled('gate_id')) {
                $log->gate_id = $request->gate_id;
            }
            $message = 'Staff re-entered gate (new visit recorded).';
            $timeKey = 'entry_time';
            $timeVal = Carbon::parse($log->entry_time)->format('h:i A');
        }

        $log->save();

        $staffData = $staff->toArray();
        $staffData['photo_url'] = $staff->photo_url;

        return response()->json([
            'success'     => true,
            'action'      => $action,         // 'checked_in' | 'checked_out'
            'message'     => $message,
            $timeKey      => $timeVal,
            'staff'       => $staffData,
        ], 200);
    }

    /**
     * List all staff currently inside the building (gate entry, no gate exit today).
     * GET /api/gate-staff-inside
     */
    public function getStaffInsideBuilding(Request $request)
    {
        // Resolve building_id from guard's gate context
        $user = Auth::user();
        $buildingId = $user->selected_building_id ?? $user->building_id ?? null;

        // Alternatively, accept building_id from query param for flexibility
        if ($request->filled('building_id')) {
            $buildingId = $request->building_id;
        }

        $today = date('Y-m-d');

        // All gate logs for today where entry exists but no exit yet
        $insideLogs = StaffAttendance::with([
                'staff' => function ($q) {
                    $q->with(['tags' => function ($tq) {
                        $tq->where('status', 'Active')->with('flat.block');
                    }]);
                }
            ])
            ->where('date', $today)
            ->whereNotNull('entry_time')
            ->whereNull('exit_time')
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
            ->get();

        $result = $insideLogs->map(function ($log) {
            $staff = $log->staff;
            if (!$staff) return null;

            $staffData = [
                'id'           => $staff->id,
                'name'         => $staff->name,
                'staff_id'     => $staff->staff_id,
                'type'         => $staff->type,
                'phone'        => $staff->phone,
                'photo_url'    => $staff->photo_url,
                'entry_time'   => $log->entry_time
                    ? Carbon::parse($log->entry_time)->format('h:i A') : null,
                'assigned_flats' => $staff->tags->map(function ($tag) {
                    $flat = $tag->flat;
                    if (!$flat) return null;
                    return [
                        'id'          => $flat->id,
                        'flat_number' => $flat->flat_number ?? $flat->name ?? $flat->id,
                        'block'       => optional($flat->block)->name,
                    ];
                })->filter()->values(),
            ];

            return $staffData;
        })->filter()->values();

        return response()->json([
            'count' => $result->count(),
            'staff' => $result,
        ], 200);
    }

    public function flatStaffCheckInOut(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staffs,id',
            'action' => 'required|in:check_in,check_out',
        ]);

        $flat = AuthHelper::flat();
        if (!$flat) return response()->json(['error' => 'Flat not found'], 404);

        $today = date('Y-m-d');
        $staffId = $request->staff_id;

        if ($request->action === 'check_in') {
            // Find or create today's society entry to get attendance_log_id
            $attendanceLog = StaffAttendance::firstOrCreate(
                ['staff_id' => $staffId, 'date' => $today],
                ['building_id' => $flat->building_id, 'status' => 'Present', 'marked_by' => Auth::id(), 'source' => 'flat']
            );

            // Create check in record
            StaffFlatAttendance::create([
                'attendance_log_id' => $attendanceLog->id,
                'staff_id' => $staffId,
                'flat_id' => $flat->id,
                'date' => $today,
                'check_in_time' => now(),
            ]);

            return response()->json(['msg' => 'Checked in successfully']);
        } else {
            // Find latest check_in without check_out today
            $record = StaffFlatAttendance::where('staff_id', $staffId)
                ->where('flat_id', $flat->id)
                ->where('date', $today)
                ->whereNull('check_out_time')
                ->latest('id')
                ->first();

            if ($record) {
                $record->update(['check_out_time' => now()]);
                return response()->json(['msg' => 'Checked out successfully']);
            } else {
                return response()->json(['error' => 'No active check-in found to check out'], 400);
            }
        }
    }

    public function getStaffData(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staffs,id',
        ]);

        $flat = AuthHelper::flat();
        if (!$flat) return response()->json(['error' => 'Flat not found'], 404);

        $staff = Staff::find($request->staff_id);

        $today = date('Y-m-d');

        // Check if currently checked in
        $activeSession = StaffFlatAttendance::where('staff_id', $staff->id)
            ->where('flat_id', $flat->id)
            ->where('date', $today)
            ->whereNull('check_out_time')
            ->first();
            
        $status = $activeSession ? 'check_in' : 'check_out';

        // Get all today's logs for this flat
        $todayLogs = StaffFlatAttendance::where('staff_id', $staff->id)
            ->where('flat_id', $flat->id)
            ->where('date', $today)
            ->orderBy('check_in_time', 'asc')
            ->get()
            ->map(function ($log) {
                return [
                    'check_in_time' => $log->check_in_time ? \Carbon\Carbon::parse($log->check_in_time)->format('h:i A') : null,
                    'check_out_time' => $log->check_out_time ? \Carbon\Carbon::parse($log->check_out_time)->format('h:i A') : null,
                ];
            });

        return response()->json([
            'info' => $staff,
            'status' => $status,
            'todayLogs' => $todayLogs
        ]);
    }

    public function getFlatStaffLogs(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staffs,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
        ]);

        $flat = AuthHelper::flat();
        if (!$flat) return response()->json(['error' => 'Flat not found'], 404);

        $logs = StaffFlatAttendance::where('staff_id', $request->staff_id)
            ->where('flat_id', $flat->id)
            ->whereMonth('date', $request->month)
            ->whereYear('date', $request->year)
            ->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get()
            ->groupBy('date');

        $formattedLogs = [];

        foreach ($logs as $date => $dailyLogs) {
            $totalMinutes = 0;
            $sessions = [];

            foreach ($dailyLogs as $log) {
                if ($log->check_in_time && $log->check_out_time) {
                    $in = \Carbon\Carbon::parse($log->check_in_time);
                    $out = \Carbon\Carbon::parse($log->check_out_time);
                    $totalMinutes += $in->diffInMinutes($out);
                }

                $sessions[] = [
                    'check_in_time' => $log->check_in_time ? \Carbon\Carbon::parse($log->check_in_time)->format('h:i A') : null,
                    'check_out_time' => $log->check_out_time ? \Carbon\Carbon::parse($log->check_out_time)->format('h:i A') : null,
                ];
            }

            $hours = floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;
            $durationStr = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";

            $formattedLogs[] = [
                'date' => $date,
                'total_duration' => $durationStr,
                'total_minutes' => $totalMinutes,
                'sessions' => $sessions,
            ];
        }

        return response()->json(['logs' => $formattedLogs], 200);
    }

    private function generateUniqueStaffId()
    {
        do {
            $id = mt_rand(100000, 999999);
        } while (Staff::where('staff_id', $id)->exists());

        return $id;
    }
}
