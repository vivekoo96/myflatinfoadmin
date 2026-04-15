<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SecurityNote;
use App\Models\BuildingUser;
use App\Models\User;
use App\Models\Gate;
use Illuminate\Support\Facades\Auth;

class SecurityNoteController extends Controller
{
    public function index(Request $request)
    {
        // Access control
        $user = Auth::user();
        if (!($user->role == 'BA' || ($user->selectedRole && $user->selectedRole->name == 'Security'))) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $user->building;

        // Load guards for the dropdown filter
        $guardRole = $this->getOrCreateGuardRole();
        $guards = collect();
        if ($guardRole) {
            $buildingUsers = BuildingUser::where('building_id', $building->id)
                ->where('role_id', $guardRole->id)
                ->with('user')
                ->get();

            $guards = $buildingUsers->map(function ($bu) {
                return (object) [
                    'id'   => $bu->user_id,
                    'name' => $bu->user->name ?? 'Unknown',
                ];
            });
        }

        // Load gates for the dropdown filter
        $gates = Gate::where('building_id', $building->id)
            ->where('deleted_at', null)
            ->orderBy('name')
            ->get();

        // Build the query
        $query = SecurityNote::where('building_id', $building->id)
            ->with(['guardUser', 'gate']);

        // Apply filters
        if ($request->filled('guard_user_id')) {
            $query->where('guard_user_id', $request->guard_user_id);
        }

        if ($request->filled('gate_id')) {
            $query->where('gate_id', $request->gate_id);
        }

        if ($request->filled('from_date')) {
            $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->from_date)->startOfDay();
            $query->whereDate('noted_at', '>=', $fromDate);
        }

        if ($request->filled('to_date')) {
            $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->to_date)->endOfDay();
            $query->whereDate('noted_at', '<=', $toDate);
        }

        // Order and paginate
        $notes = $query->orderBy('noted_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // Collect applied filters for view
        $filters = [
            'guard_user_id' => $request->input('guard_user_id'),
            'gate_id'       => $request->input('gate_id'),
            'from_date'     => $request->input('from_date'),
            'to_date'       => $request->input('to_date'),
        ];

        return view('admin.security_notes.index', compact('notes', 'guards', 'gates', 'filters'));
    }

    /**
     * Helper to get or create the guard role
     */
    private function getOrCreateGuardRole()
    {
        return \App\Models\Role::whereRaw("LOWER(TRIM(COALESCE(slug, ''))) = ?", ['guard'])
            ->first();
    }
}
