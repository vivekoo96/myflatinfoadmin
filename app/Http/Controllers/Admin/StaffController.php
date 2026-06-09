<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffTag;
use App\Models\Building;
use App\Models\User;
use App\Models\BuildingUser;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
            'phone'           => 'required|string|max:20',
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
}
