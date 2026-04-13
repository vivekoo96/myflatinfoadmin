<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeetingMinute;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MeetingController extends Controller
{
    public function index()
    {
        if (! $this->isAllowed()) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $this->getCurrentBuilding();
        $meetings = collect();

        if ($building) {
            $meetings = MeetingMinute::where('building_id', $building->id)
                ->orderBy('date', 'desc')
                ->orderBy('time', 'desc')
                ->get();
        }

        return view('admin.meeting.index', compact('building', 'meetings'));
    }

    public function store(Request $request)
    {
        if (! $this->isAllowed()) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'date'        => 'nullable|date_format:Y-m-d',
            'time'        => 'nullable|date_format:H:i',
        ]);

        $building = $this->getCurrentBuilding();
        if (! $building) {
            return redirect()->back()->with('error', 'Building context not found.');
        }

        // Set default date/time to current if not provided
        $date = $request->date ?? Carbon::now()->format('Y-m-d');
        $time = $request->time ?? Carbon::now()->format('H:i');

        // Get user role
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
            'date'            => $date,
            'time'            => $time,
            'created_by'      => $user->id,
            'created_by_role' => $role,
        ]);

        return redirect()->back()->with('success', 'Meeting created successfully.');
    }

    public function destroy($id)
    {
        if (! $this->isAllowed()) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $this->getCurrentBuilding();
        $minute   = MeetingMinute::where('id', $id)
            ->where('building_id', $building->id)
            ->firstOrFail();

        $minute->delete();

        return redirect()->back()->with('success', 'Meeting deleted successfully.');
    }

    // ──────────────────────────────────────────────────────────────
    private function isAllowed(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->role === 'BA' ||
            ($user->selectedRole && in_array($user->selectedRole->slug, ['president', 'secretary']))
        );
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
        if (! empty($assigned) && is_iterable($assigned)) {
            foreach ($assigned as $b) return $b;
        }

        return null;
    }
}
