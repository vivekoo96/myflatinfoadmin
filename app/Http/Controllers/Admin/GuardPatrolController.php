<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuardPatrol;
use App\Models\PatrolLocation;
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

        $locations = PatrolLocation::where('building_id', $building->id)->get();

        $query = GuardPatrol::where('building_id', $building->id)
            ->with(['guardUser', 'patrolLocation']);

        // Apply filters
        if ($request->filled('guard_user_id')) {
            $query->where('guard_user_id', $request->guard_user_id);
        }
        if ($request->filled('patrol_location_id')) {
            $query->where('patrol_location_id', $request->patrol_location_id);
        }
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }
        if ($request->filled('checkin_type')) {
            $query->where('checkin_type', $request->checkin_type);
        }
        if ($request->filled('date')) {
            $query->whereDate('checked_in_at', $request->date);
        }

        $patrols = $query->orderBy('checked_in_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $filters = array_merge(
            ['guard_user_id' => '', 'patrol_location_id' => '', 'shift' => '', 'checkin_type' => '', 'date' => ''],
            $request->only(['guard_user_id', 'patrol_location_id', 'shift', 'checkin_type', 'date'])
        );

        return view('admin.guard_patrols.index', compact('patrols', 'guards', 'locations', 'filters'));
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

        return view('admin.guard_patrols.checkin', compact('locations', 'building'));
    }

    public function submitCheckin(Request $request)
    {
        $rules = [
            'patrol_location_id' => 'required|exists:patrol_locations,id',
            'checkin_type' => 'required|in:photo,qr',
            'shift' => 'required|in:Day,Night',
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
