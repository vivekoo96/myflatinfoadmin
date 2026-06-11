<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffTag;
use App\Models\StaffAttendance;
use App\Models\StaffFlatAttendance;
use App\Models\Building;
use App\Models\User;
use App\Models\BuildingUser;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;


class StaffController extends Controller
{
    public function index(Request $request)
    {
        if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President') || Auth::User()->hasPermission('custom.staff_attendance'))
        {
            // Access granted
        } else {
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        // Fetch Staff records
        $staffQuery = Staff::where('building_id', Auth::user()->building_id)
            ->where('approval_status', 'Approved')
            ->with(['activeTag.flat.block']);

        if ($request->has('search')) {
            $staffQuery->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('staff_id', 'like', '%' . $request->search . '%');
        }

        if ($request->has('category')) {
            $staffQuery->where('category', $request->category);
        }

        if ($request->has('type')) {
            $staffQuery->where('type', $request->type);
        }

        $staffs = $staffQuery->latest()->paginate(10);
        $staffs->getCollection()->transform(function($staff) {
            $staff->source = 'staff';
            return $staff;
        });

        /* 
        // Fetch Building Workers (from BuildingUser with Building Worker role)
        $guardRole = Role::whereRaw("LOWER(TRIM(COALESCE(slug, ''))) = ?", ['guard'])->first();
        $buildingWorkers = collect();

        if ($guardRole) {
            $workers = BuildingUser::where('building_id', Auth::user()->building_id)
                ->where('role_id', $guardRole->id)
                ->with('user')
                ->get();

            foreach ($workers as $worker) {
                if ($worker->user) {
                    $worker->user->source = 'building_worker';
                    $worker->user->building_user_id = $worker->id;
                    $worker->user->category = 'building_staff';
                    $worker->user->type = $guardRole->name ?? 'Guard';
                    $worker->user->status = $worker->status;
                    $worker->user->staff_id = null;
                    $buildingWorkers->push($worker->user);
                }
            }
        }

        // Merge both collections
        $allStaff = $staffs->getCollection()->merge($buildingWorkers);
        $staffs->setCollection($allStaff);
        */

        return view('admin.staff.index', compact('staffs'));
    }

    public function pending(Request $request)
    {
        if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President') || Auth::User()->hasPermission('custom.staff_attendance'))
        {
            // Access granted
        } else {
            return redirect('permission-denied')->with('error','Permission denied!');
        }

        $staffs = Staff::where('building_id', Auth::user()->building_id)
            ->where('approval_status', 'Pending')
            ->with(['activeTag.flat.block'])
            ->latest()
            ->paginate(10);
            
        $staffs->getCollection()->transform(function($staff) {
            $staff->source = 'staff';
            return $staff;
        });

        return view('admin.staff.pending', compact('staffs'));
    }

    public function approve(Staff $staff)
    {
        $staff->approval_status = 'Approved';
        $staff->save();
        return redirect()->back()->with('success', 'Staff member approved successfully.');
    }

    public function reject(Staff $staff)
    {
        $staff->approval_status = 'Rejected';
        $staff->save();
        return redirect()->back()->with('success', 'Staff member rejected successfully.');
    }

    public function show(Staff $staff)
    {
        return response()->json($staff);
    }

    public function create()
    {
        $building = Auth::user()->building;
        $blocks   = $building ? $building->blocks : collect();

        $staffTypes = \App\Models\StaffType::where(function($query) {
                          $query->where('building_id', Auth::user()->building_id)
                                ->orWhereNull('building_id');
                      })
                      ->pluck('name')
                      ->toArray();
        $types = Staff::where('building_id', Auth::user()->building_id)
                      ->select('type')
                      ->distinct()
                      ->pluck('type')
                      ->toArray();
        $allTypes = array_unique(array_merge($types, $staffTypes));

        return view('admin.staff.create', compact('blocks', 'allTypes'));
    }

    public function store(Request $request)
    {
        $data = $this->validateStaff($request);

        $staff = new Staff();
        $staff->name                  = $data['name'];
        $staff->phone                 = $data['phone'];
        $staff->address               = $request->address;
        $staff->type                  = $data['type'];
        $staff->category              = $request->category ?: 'flat_staff';
        $staff->is_open_to_all        = $request->boolean('is_open_to_all');
        $staff->status                = $request->status ?: 'Active';
        $staff->document_status       = $request->document_status;
        $staff->staff_id              = $this->generateUniqueStaffId();
        $staff->building_id           = Auth::user()->building_id;
        $staff->creator_id            = Auth::id();
        $staff->creator_type          = 'admin';

        if ($path = $this->uploadStaffFile($request, 'photo', 'uploads/staff')) {
            $staff->photo = $path;
        }
        if ($path = $this->uploadStaffFile($request, 'document', 'uploads/staff/documents')) {
            $staff->document_verification = $path;
        }
        if ($path = $this->uploadStaffFile($request, 'noc', 'uploads/staff/noc')) {
            $staff->noc_police = $path;
        }

        $staff->save();

        $this->syncFlatTag($request, $staff);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff registered successfully. Staff ID (gate entry): ' . $staff->staff_id);
    }

    private function generateUniqueStaffId()
    {
        do {
            $id = mt_rand(100000, 999999);
        } while (Staff::where('staff_id', $id)->exists());

        return $id;
    }

    public function edit(Staff $staff)
    {
        $staff->load('activeTag.flat.block');
        $building = Auth::user()->building;
        $blocks   = $building ? $building->blocks : collect();

        $staffTypes = \App\Models\StaffType::where(function($query) {
                          $query->where('building_id', Auth::user()->building_id)
                                ->orWhereNull('building_id');
                      })
                      ->pluck('name')
                      ->toArray();
        $types = Staff::where('building_id', Auth::user()->building_id)
                      ->select('type')
                      ->distinct()
                      ->pluck('type')
                      ->toArray();
        $allTypes = array_unique(array_merge($types, $staffTypes));

        return view('admin.staff.edit', compact('staff', 'blocks', 'allTypes'));
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $this->validateStaff($request);

        $staff->name            = $data['name'];
        $staff->phone           = $data['phone'];
        $staff->address         = $request->address;
        $staff->type            = $data['type'];
        $staff->category        = $request->category ?: $staff->category;
        $staff->is_open_to_all  = $request->boolean('is_open_to_all');
        $staff->status          = $request->status ?: $staff->status;
        $staff->document_status = $request->document_status;

        if ($path = $this->uploadStaffFile($request, 'photo', 'uploads/staff')) {
            $staff->photo = $path;
        }
        if ($path = $this->uploadStaffFile($request, 'document', 'uploads/staff/documents')) {
            $staff->document_verification = $path;
        }
        if ($path = $this->uploadStaffFile($request, 'noc', 'uploads/staff/noc')) {
            $staff->noc_police = $path;
        }

        $staff->save();

        $this->syncFlatTag($request, $staff);

        return redirect()->route('admin.staff.index')->with('success', 'Staff updated successfully.');
    }

    public function toggleStatus(Staff $staff)
    {
        if ($staff->building_id != Auth::user()->building_id) {
            return redirect()->back()->with('error', 'Not allowed.');
        }
        $staff->status = $staff->status === 'Active' ? 'Inactive' : 'Active';
        $staff->save();
        return redirect()->back()->with('success', 'Staff marked ' . $staff->status . '.');
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();
        return redirect()->route('admin.staff.index')->with('success', 'Staff deleted successfully.');
    }

    /**
     * Shared validation for register/update. Flat assignment is only required
     * when the staff is NOT open to all flats.
     */
    private function validateStaff(Request $request): array
    {
        return $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => ['required', 'string', 'regex:/^[6789]\d{9}$/'],
            'type'            => 'required|string|max:50',
            'category'        => 'nullable|in:flat_staff,building_staff,external_staff',
            'address'         => 'nullable|string|max:1000',
            'status'          => 'nullable|in:Active,Inactive',
            'photo'           => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'document'        => 'nullable|mimes:jpeg,png,jpg,pdf|max:8192',
            'noc'             => 'nullable|mimes:jpeg,png,jpg,pdf|max:8192',
            'document_status' => 'nullable|in:Pending,Verified',
            'is_open_to_all'  => 'nullable|boolean',
            'flat_id'         => 'nullable|exists:flats,id',
            'engagement_type' => 'nullable|in:In-house,Timely-basis',
            'time_slot'       => 'nullable|string|max:100',
        ]);
    }

    /**
     * Move an uploaded file into public/<dir> and return its relative path,
     * or null if no file was sent. Mirrors the existing photo-upload pattern.
     */
    private function uploadStaffFile(Request $request, string $field, string $dir): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }
        $file = $request->file($field);
        $name = $field . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file->getClientOriginalExtension();
        $file->move(public_path($dir), $name);
        return $dir . '/' . $name;
    }

    /**
     * Create/update/remove the single-flat assignment for a staff.
     * - Open to all flats  -> remove any tag.
     * - A flat is selected -> upsert the tag with engagement + time slot.
     */
    private function syncFlatTag(Request $request, Staff $staff): void
    {
        if ($request->boolean('is_open_to_all') || !$request->filled('flat_id')) {
            StaffTag::where('staff_id', $staff->id)->delete();
            return;
        }

        $flat = \App\Models\Flat::find($request->flat_id);
        if (!$flat || $flat->building_id != $staff->building_id) {
            return;
        }

        StaffTag::updateOrCreate(
            ['staff_id' => $staff->id],
            [
                'flat_id'         => $flat->id,
                'building_id'     => $staff->building_id,
                'engagement_type' => $request->engagement_type,
                'time_slot'       => $request->engagement_type === 'Timely-basis' ? $request->time_slot : null,
                'status'          => 'Active',
            ]
        );
    }

    public function storeType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50'
        ]);

        $type = \App\Models\StaffType::firstOrCreate([
            'building_id' => Auth::user()->building_id,
            'name' => trim($request->name)
        ]);

        return response()->json(['success' => true, 'type' => $type->name]);
    }

    /**
     * Show admin attendance log page.
     * Step 1: Select staff (includes deleted/inactive).
     * Step 2: Select date.
     * Step 3: See full gate + flat check-in/out details.
     * GET /staff/attendance-logs
     */
    public function attendanceLogs(Request $request)
    {
        if (Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'President') || Auth::User()->hasPermission('custom.staff_attendance')) {
            // Access granted
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $buildingId = Auth::user()->building_id;

        // ALL staff for this building — including inactive (no SoftDeletes on Staff model).
        $allStaff = Staff::where('building_id', $buildingId)
            ->orderBy('name')
            ->get(['id', 'name', 'staff_id', 'type', 'status', 'photo']);

        $selectedStaffId = $request->get('staff_id');
        $selectedDate    = $request->get('date', date('Y-m-d'));

        $gateLog      = null;
        $flatSessions = collect();
        $staff        = null;

        if ($selectedStaffId) {
            $staff = Staff::where('building_id', $buildingId)->find($selectedStaffId);

            if ($staff) {
                // Gate-level log for this date
                $gateLog = StaffAttendance::where('staff_id', $staff->id)
                    ->where('date', $selectedDate)
                    ->first();

                // All flat sessions for this date, eager-load flat + block
                $rawSessions = StaffFlatAttendance::where('staff_id', $staff->id)
                    ->where('date', $selectedDate)
                    ->with('flat.block')
                    ->orderBy('check_in_time')
                    ->get();

                // Group by flat_id, calculate durations
                $flatSessions = $rawSessions->groupBy('flat_id')->map(function ($sessions, $flatId) {
                    $first   = $sessions->first();
                    $flat    = $first->flat;
                    $block   = optional($flat?->block)->name;
                    $flatNum = $flat?->flat_number ?? $flat?->name ?? "Flat #{$flatId}";

                    $sessionDetails = $sessions->map(function ($s) {
                        $inTime  = $s->check_in_time  ? Carbon::parse($s->check_in_time)  : null;
                        $outTime = $s->check_out_time ? Carbon::parse($s->check_out_time) : null;

                        $duration = null;
                        if ($inTime && $outTime) {
                            $mins     = $inTime->diffInMinutes($outTime);
                            $h        = floor($mins / 60);
                            $m        = $mins % 60;
                            $duration = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                        }

                        return [
                            'check_in_time'  => $inTime  ? $inTime->format('h:i A')  : null,
                            'check_out_time' => $outTime ? $outTime->format('h:i A') : null,
                            'duration'       => $duration,
                            'is_open'        => $inTime && !$outTime,
                        ];
                    });

                    $totalMinutes = $sessions->sum(function ($s) {
                        if ($s->check_in_time && $s->check_out_time) {
                            return Carbon::parse($s->check_in_time)->diffInMinutes(Carbon::parse($s->check_out_time));
                        }
                        return 0;
                    });

                    $th = floor($totalMinutes / 60);
                    $tm = $totalMinutes % 60;

                    return [
                        'flat_id'        => $flatId,
                        'flat_number'    => $flatNum,
                        'block'          => $block,
                        'sessions'       => $sessionDetails,
                        'total_duration' => $th > 0 ? "{$th}h {$tm}m" : "{$tm}m",
                        'total_minutes'  => $totalMinutes,
                    ];
                })->values();
            }
        }

        return view('admin.staff.attendance_logs', compact(
            'allStaff', 'selectedStaffId', 'selectedDate', 'staff', 'gateLog', 'flatSessions'
        ));
    }
}

