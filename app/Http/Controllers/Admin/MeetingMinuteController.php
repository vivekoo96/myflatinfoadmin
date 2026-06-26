<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MeetingMinute;
use App\Models\Building;
use App\Models\Flat;
use App\Helpers\NotificationHelper2;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MeetingMinuteController extends Controller
{
    public function index(Request $request)
    {
        if (! $this->isAllowed()) {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        $building = $this->getCurrentBuilding();
        $minutes  = collect();

        if ($building) {
            $query = MeetingMinute::where('building_id', $building->id)
                ->with('creator');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('from_date')) {
                $query->whereDate('datetime', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('datetime', '<=', $request->to_date);
            }

            $minutes = $query->orderBy('datetime', 'desc')->get();
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
            'datetime'    => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        $building = $this->getCurrentBuilding();
        if (! $building) {
            return redirect()->back()->with('error', 'Building context not found.');
        }

        // Set default datetime to current if not provided
        $datetime = $request->datetime ?? Carbon::now()->format('Y-m-d\TH:i:s');

        $user = Auth::user();

        if ($user->role === 'BA') {
            $role = 'Building Admin';
        } elseif ($user->selectedRole) {
            $role = $user->selectedRole->name ?? ucfirst($user->selectedRole->slug);
        } else {
            $role = 'Building Admin';
        }

        $minute = MeetingMinute::create([
            'building_id'     => $building->id,
            'title'           => $request->title,
            'description'     => $request->description,
            'datetime'        => $datetime,
            'created_by'      => $user->id,
            'created_by_role' => $role,
        ]);

        // Send notifications to all flat owners and tenants in the building
        $flats = Flat::where('building_id', $building->id)
            ->where(function ($query) {
                $query->whereNotNull('owner_id')
                      ->orWhereNotNull('tanent_id');
            })
            ->get();

        foreach ($flats as $flat) {
            // Collect owner and tenant (skip nulls and duplicates)
            $userIds = collect([$flat->owner_id, $flat->tanent_id])
                ->filter()
                ->unique()
                ->values();

            foreach ($userIds as $userId) {
                $dataPayload = [
                    'screen'       => 'MeetingMinutes',
                    'params'       => json_encode([
                        'screenTab'   => 'MeetingMinutes',
                        'mom_id'      => (string) $minute->id,
                        'building_id' => (string) $building->id,
                        'flat_id'     => (string) $flat->id,
                        'user_id'     => (string) $userId,
                    ]),
                    'categoryId'   => 'MeetingMinutes',
                    'channelId'    => 'default',
                    'sound'        => 'bellnotificationsound.wav',
                    'type'         => 'MEETING_MINUTE',
                ];

                try {
                    NotificationHelper2::sendNotification(
                        $userId,
                        'Apartment Meeting Update',
                        'Minutes of the meeting have been uploaded. Don\'t forget to review them.',
                        $dataPayload,
                        [
                            'type'        => 'meeting_minute',
                            'building_id' => $building->id,
                            'flat_id'     => $flat->id,
                            'from_id'     => Auth::id(),
                        ],
                        ['user']
                    );
                } catch (\Throwable $e) {
                    Log::error('Meeting minute notification failed', [
                        'user_id'   => $userId,
                        'flat_id'   => $flat->id,
                        'minute_id' => $minute->id,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }
        }

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
