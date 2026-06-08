<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CommunityActivity;
use Illuminate\Support\Facades\Auth;

class ActivityAdminController extends Controller
{
    /**
     * List pending activities for approval
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return redirect('permission-denied')->with('error', 'Building context not found.');
        }

        $status = $request->get('status', 'pending');
        $search = $request->get('search', '');

        $query = CommunityActivity::where('building_id', $building->id)
            ->with(['creator', 'responses'])
            ->orderBy('activity_datetime', 'desc');

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $activities = $query->paginate(20);

        return view('admin.activity.index', compact('building', 'activities', 'status', 'search'));
    }

    /**
     * Show activity detail for approval
     */
    public function show($id)
    {
        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return redirect('permission-denied')->with('error', 'Building context not found.');
        }

        $activity = CommunityActivity::where('id', $id)
            ->where('building_id', $building->id)
            ->with(['creator', 'responses'])
            ->first();

        if (!$activity) {
            return redirect()->back()->with('error', 'Activity not found.');
        }

        return view('admin.activity.show', compact('building', 'activity'));
    }

    /**
     * Approve an activity
     */
    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return response()->json(['error' => 'Building context not found.'], 403);
        }

        $activity = CommunityActivity::where('id', $id)
            ->where('building_id', $building->id)
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'Activity not found.'], 404);
        }

        $activity->status = 'approved';
        $activity->save();

        // Notify ONLY the poster (no audience blast), same as classified approval.
        $this->notifyActivityOwner($activity, 'approved');

        return response()->json([
            'message' => 'Activity approved successfully',
            'activity' => $activity
        ], 200);
    }

    /**
     * Reject an activity
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return response()->json(['error' => 'Building context not found.'], 403);
        }

        $activity = CommunityActivity::where('id', $id)
            ->where('building_id', $building->id)
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'Activity not found.'], 404);
        }

        $activity->status = 'rejected';
        $activity->save();

        // Notify ONLY the poster (no audience blast), same as classified rejection.
        $this->notifyActivityOwner($activity, 'rejected');

        return response()->json([
            'message' => 'Activity rejected successfully',
            'activity' => $activity
        ], 200);
    }

    /**
     * Delete an activity
     */
    public function delete(Request $request, $id)
    {
        $user = Auth::user();
        $building = $user->building;

        if (!$building) {
            return response()->json(['error' => 'Building context not found.'], 403);
        }

        $activity = CommunityActivity::where('id', $id)
            ->where('building_id', $building->id)
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'Activity not found.'], 404);
        }

        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully'
        ], 200);
    }

    /**
     * Notify ONLY the activity's poster about its approval/rejection.
     * No audience blast — mirrors the classified approval behaviour.
     */
    private function notifyActivityOwner(CommunityActivity $activity, string $status): void
    {
        if (!$activity->user_id) {
            return;
        }

        if ($status === 'approved') {
            $title = 'Activity Approved';
            $body  = 'Your community activity "' . $activity->title . '" has been approved.';
        } else {
            $title = 'Activity Rejected';
            $body  = 'Your community activity "' . $activity->title . '" was rejected.';
        }

        $dataPayload = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'screen'       => 'CommunityActivities',
            'params'       => json_encode([
                'activity_id' => (string) $activity->id,
                'building_id' => (string) $activity->building_id,
            ]),
            'categoryId'   => 'Community',
            'channelId'    => 'Community',
            'sound'        => 'bellnotificationsound.wav',
            'type'         => 'COMMUNITY_ACTIVITY_STATUS',
        ];

        try {
            // NotificationHelper2 saves the in-app notification AND pushes to the
            // poster's app (resident app => app_name 'user'). Poster only.
            \App\Helpers\NotificationHelper2::sendNotification(
                $activity->user_id,
                $title,
                $body,
                $dataPayload,
                [
                    'from_id'     => Auth::id(),
                    'flat_id'     => $activity->flat_id,
                    'building_id' => $activity->building_id,
                    'type'        => 'community_activity_status',
                    'ios_sound'   => $dataPayload['sound'],
                ],
                ['user']
            );
        } catch (\Throwable $e) {
            \Log::error('Activity owner notification failed', [
                'activity_id' => $activity->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
