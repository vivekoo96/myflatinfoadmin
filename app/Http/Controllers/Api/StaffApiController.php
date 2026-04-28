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

    public function addFlatEmp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string',
            'type' => 'required|string',
            'time_slot' => 'nullable|string',
            'photo' => 'nullable|string', // Base64 or URL
        ]);

        if ($validator->fails()) return response()->json(['error' => $validator->errors()->first()], 422);

        $flat = AuthHelper::flat();
        
        $staff = new Staff();
        $staff->name = $request->name;
        $staff->phone = $request->phone;
        $staff->type = $request->type;
        $staff->category = 'flat_staff';
        $staff->building_id = $flat->building_id;
        $staff->staff_id = $this->generateUniqueStaffId();
        $staff->creator_id = Auth::id();
        $staff->creator_type = 'flat_user';
        
        if ($request->photo) {
            // Logic for photo saving if needed
        }
        
        $staff->save();

        // Tag to flat
        StaffTag::create([
            'staff_id' => $staff->id,
            'flat_id' => $flat->id,
            'building_id' => $flat->building_id,
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
            ['building_id' => $flat->building_id, 'status' => 'Present']
        );

        StaffFlatAttendance::create([
            'attendance_log_id' => $attendanceLog->id,
            'staff_id' => $request->staff_id,
            'flat_id' => $flat->id,
            'marked_at' => now(),
        ]);

        return response()->json(['msg' => 'Attendance marked present for your flat'], 200);
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
