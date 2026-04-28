<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President') || Auth::User()->hasPermission('custom.staff_attendance'))
        {
            // Access granted
        } else {
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        $date = $request->get('date', date('Y-m-d'));
        $query = Staff::where('building_id', Auth::user()->building_id)
            ->with(['attendanceLogs' => function($q) use ($date) {
                $q->where('date', $date);
            }]);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $staffs = $query->get();

        return view('admin.attendance.index', compact('staffs', 'date'));
    }

    public function markAttendance(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staffs,id',
            'status' => 'required|in:Present,Absent,On Leave',
            'date' => 'required|date',
        ]);

        $staff = Staff::findOrFail($request->staff_id);
        
        $attendance = StaffAttendance::updateOrCreate(
            [
                'staff_id' => $staff->id,
                'date' => $request->date,
            ],
            [
                'building_id' => $staff->building_id,
                'status' => $request->status,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Attendance marked successfully']);
    }

    public function report(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        
        $staffs = Staff::where('building_id', Auth::user()->building_id)
            ->with(['attendanceLogs' => function($q) use ($month, $year) {
                $q->whereMonth('date', $month)->whereYear('date', $year);
            }])->get();

        return view('admin.attendance.report', compact('staffs', 'month', 'year'));
    }
}
