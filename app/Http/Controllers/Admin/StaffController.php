<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
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
        $staffQuery = Staff::where('building_id', Auth::user()->building_id);

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

        return view('admin.staff.index', compact('staffs'));
    }

    public function create()
    {
        return view('admin.staff.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'type' => 'required|string|max:50',
            'category' => 'required|in:flat_staff,building_staff,external_staff',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $staff = new Staff($request->all());
        $staff->staff_id = $this->generateUniqueStaffId();
        $staff->building_id = Auth::user()->building_id;
        $staff->creator_id = Auth::id();
        $staff->creator_type = 'admin';

        if ($request->hasFile('photo')) {
            $imageName = time() . '.' . $request->photo->extension();
            $request->photo->move(public_path('uploads/staff'), $imageName);
            $staff->photo = 'uploads/staff/' . $imageName;
        }

        $staff->save();

        return redirect()->route('admin.staff.index')->with('success', 'Staff created successfully. ID: ' . $staff->staff_id);
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
        return view('admin.staff.edit', compact('staff'));
    }

    public function update(Request $request, Staff $staff)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'status' => 'required|in:Active,Inactive',
        ]);

        $staff->update($request->all());

        if ($request->hasFile('photo')) {
            $imageName = time() . '.' . $request->photo->extension();
            $request->photo->move(public_path('uploads/staff'), $imageName);
            $staff->photo = 'uploads/staff/' . $imageName;
            $staff->save();
        }

        return redirect()->route('admin.staff.index')->with('success', 'Staff updated successfully.');
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();
        return redirect()->route('admin.staff.index')->with('success', 'Staff deleted successfully.');
    }
}
