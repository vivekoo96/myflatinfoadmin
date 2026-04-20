<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardPatrol;
use App\Models\PatrolLocation;
use Illuminate\Http\Request;
use Auth;

class GuardPatrolController extends Controller
{
    public function getLocations(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first using select-gate endpoint'], 403);
        }

        $building = $gate->building;

        $locations = PatrolLocation::where('building_id', $building->id)
            ->where('status', 'Active')
            ->select('id', 'name', 'description', 'qr_string')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
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
            return response()->json([
                'success' => false,
                'message' => $validation->errors()->first(),
            ], 422);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first using select-gate endpoint'], 403);
        }

        $building = $gate->building;

        // Ensure location belongs to this building
        $location = PatrolLocation::where('id', $request->patrol_location_id)
            ->where('building_id', $building->id)
            ->first();

        if (!$location) {
            return response()->json(['success' => false, 'message' => 'Location not found'], 404);
        }

        $photo_url = null;
        if ($request->checkin_type === 'photo' && $request->hasFile('photo')) {
            if (!file_exists(public_path('/public/images/patrols/'))) {
                mkdir(public_path('/public/images/patrols/'), 0755, true);
            }

            $file = $request->file('photo');
            $ext = $file->getClientOriginalExtension();
            $filename = 'patrols/' . uniqid() . '.' . $ext;
            $file->move(public_path('/public/images/patrols/'), $filename);
            $photo_url = $filename;
        }

        if ($request->checkin_type === 'qr') {
            // Verify QR code matches the location
            if ($location->qr_string !== $request->qr_scanned_value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code for the selected location.',
                ], 422);
            }
        }

        try {
            $patrol = GuardPatrol::create([
                'building_id' => $building->id,
                'guard_user_id' => $user->id,
                'patrol_location_id' => $request->patrol_location_id,
                'checkin_type' => $request->checkin_type,
                'shift' => $request->shift,
                'photo_url' => $photo_url,
                'qr_scanned_value' => $request->checkin_type === 'qr' ? $request->qr_scanned_value : null,
                'checked_in_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-in recorded successfully!',
                'data' => [
                    'id' => $patrol->id,
                    'location' => $location->name,
                    'checked_in_at' => $patrol->checked_in_at->format('Y-m-d H:i:s'),
                    'type' => $patrol->checkin_type,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Guard patrol check-in failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record check-in. Please try again.',
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $gate = $user->gate;
        if (!$gate || !$gate->building) {
            return response()->json(['success' => false, 'message' => 'Please select a gate first using select-gate endpoint'], 403);
        }

        $building = $gate->building;

        $query = GuardPatrol::where('building_id', $building->id)
            ->where('guard_user_id', $user->id)
            ->with(['patrolLocation']);

        // Optional filters
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }
        if ($request->filled('date')) {
            $query->whereDate('checked_in_at', $request->date);
        }
        if ($request->filled('days')) {
            // Last N days
            $days = (int)$request->days;
            if ($days > 0 && $days <= 365) {
                $query->where('checked_in_at', '>=', now()->subDays($days));
            }
        }

        $patrols = $query->orderBy('checked_in_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($patrol) {
                return [
                    'id' => $patrol->id,
                    'location' => $patrol->patrolLocation ? $patrol->patrolLocation->name : 'N/A',
                    'shift' => $patrol->shift,
                    'type' => $patrol->checkin_type,
                    'checked_in_at' => $patrol->checked_in_at->format('Y-m-d H:i:s'),
                    'photo_url' => $patrol->photo_url ? asset('images/' . $patrol->photo_url) : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $patrols,
            'count' => $patrols->count(),
        ]);
    }
}
