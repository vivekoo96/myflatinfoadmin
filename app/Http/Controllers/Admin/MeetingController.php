<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            $meetings = Meeting::where('building_id', $building->id)
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

        Meeting::create([
            'building_id' => $building->id,
            'title'       => $request->title,
            'description' => $request->description,
            'date'        => $request->date,
            'time'        => $request->time,
            'created_by'  => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Meeting created successfully.');
    }

    public function destroy($id)
    {
        if (! $this->isAllowed()) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $this->getCurrentBuilding();
        $meeting  = Meeting::where('id', $id)
            ->where('building_id', $building->id)
            ->firstOrFail();

        $meeting->delete();

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
