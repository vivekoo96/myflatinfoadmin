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
}
