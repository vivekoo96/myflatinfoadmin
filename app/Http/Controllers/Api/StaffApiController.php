<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
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
            ->where('type', '!=', 'Security Guard')
            ->with(['tags']);

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

        $staffs->getCollection()->transform(function ($staff) use ($flat) {
            $staff->is_added = $staff->tags->contains('flat_id', $flat->id);
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
            'photo' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
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

    // --- Security Gate Endpoints ---

    public function gateStaffPunch(Request $request)
    {
        $request->validate([
            'staff_id_code' => 'required|string', // 6-digit code
            'gate_id' => 'required',
            'action' => 'required|in:entry,exit',
        ]);

        $staff = Staff::where('staff_id', $request->staff_id_code)->first();
        if (!$staff) return response()->json(['error' => 'Invalid Staff ID'], 404);

        $today = date('Y-m-d');
        
        $log = StaffAttendance::firstOrNew(['staff_id' => $staff->id, 'date' => $today]);
        $log->building_id = $staff->building_id;
        $log->gate_id = $request->gate_id;
        $log->source = 'gate';
        $log->marked_by = Auth::id();
        
        if ($request->action == 'entry') {
            $log->entry_time = now();
            $log->status = 'Present';
        } else {
            $log->exit_time = now();
        }
        
        $log->save();

        return response()->json(['msg' => 'Punch recorded successfully', 'staff' => $staff], 200);
    }

    public function verifyStaffCode(Request $request)
    {
        $staff = Staff::where('staff_id', $request->staff_id_code)->first();
        if (!$staff) return response()->json(['error' => 'Invalid Staff ID'], 404);

        return response()->json(['staff' => $staff], 200);
    }

    private function generateUniqueStaffId()
    {
        do {
            $id = mt_rand(100000, 999999);
        } while (Staff::where('staff_id', $id)->exists());

        return $id;
    }
}
