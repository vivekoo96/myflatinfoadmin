<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MeetingMinute;
use App\Models\Building;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MeetingMinuteController extends Controller
{
    public function index()
    {
        if (! $this->isAllowed()) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $this->getCurrentBuilding();
        $minutes  = collect();

        if ($building) {
            $minutes = MeetingMinute::where('building_id', $building->id)
                ->with('creator')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('admin.meeting_minute.index', compact('building', 'minutes'));
    }

    public function store(Request $request)
    {
        if (! $this->isAllowed()) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $building = $this->getCurrentBuilding();
        if (! $building) {
            return redirect()->back()->with('error', 'Building context not found.');
        }

        $user = Auth::user();

        if ($user->role === 'BA') {
            $role = 'Building Admin';
        } elseif ($user->selectedRole) {
            $role = $user->selectedRole->name ?? ucfirst($user->selectedRole->slug);
        } else {
            $role = 'Building Admin';
        }

        MeetingMinute::create([
            'building_id'     => $building->id,
            'title'           => $request->title,
            'description'     => $request->description,
            'created_by'      => $user->id,
            'created_by_role' => $role,
        ]);

        return redirect()->back()->with('success', 'Meeting minutes saved successfully.');
    }

    // ─── No edit / delete per business rules ────────────────

    private function isAllowed(): bool
    {
        $user = Auth::user();
        return $user && ($user->role === 'BA' || ($user->selectedRole && $user->selectedRole->slug === 'president'));
    }

    private function getCurrentBuilding(): ?Building
    {
        $user = Auth::user();
        if (! $user) return null;

        if ($user->building) return $user->building;

        if (! empty($user->building_id)) {
            $b = Building::find($user->building_id);
            if ($b) return $b;
        }

        $assigned = method_exists($user, 'allBuildings') ? $user->allBuildings() : [];
        if (! empty($assigned)) return $assigned[0];

        return null;
    }
}
