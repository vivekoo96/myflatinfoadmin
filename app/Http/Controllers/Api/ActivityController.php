<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityActivity;
use App\Models\CommunityActivityResponse;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ActivityController extends Controller
{
    /**
     * Create a community activity post
     */
    public function create(Request $request)
    {
        $rules = [
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'location'           => 'nullable|string|max:255',
            'activity_datetime'  => 'required|date_format:Y-m-d\TH:i:s',
            'response_type'      => 'required|in:simple,detailed',
            'post_type'          => 'required|in:slot,unlimited',
            'max_slots'          => 'required_if:post_type,slot|integer|min:1',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->first()], 422);
        }

        $flat = AuthHelper::flat();
        if (!$flat) {
            return response()->json(['error' => 'Flat not assigned'], 404);
        }

        $user = Auth::user();

        // Check max posts limit
        $activePostCount = CommunityActivity::where('user_id', $user->id)
            ->where('building_id', $flat->building_id)
            ->whereNull('deleted_at')
            ->count();

        if ($activePostCount >= $flat->building->max_activity_posts) {
            return response()->json([
                'error' => 'You have reached the maximum number of activity posts allowed (' . $flat->building->max_activity_posts . ')'
            ], 422);
        }

        $activity = CommunityActivity::create([
            'building_id'        => $flat->building_id,
            'user_id'            => $user->id,
            'flat_id'            => $flat->id,
            'title'              => $request->title,
            'description'        => $request->description,
            'location'           => $request->location,
            'activity_datetime'  => Carbon::createFromFormat('Y-m-d\TH:i:s', $request->activity_datetime),
            'response_type'      => $request->response_type,
            'post_type'          => $request->post_type,
            'max_slots'          => $request->post_type === 'slot' ? $request->max_slots : null,
            'status'             => 'pending',
        ]);

        return response()->json([
            'activity' => $activity,
            'message'  => 'Activity created successfully and is pending approval'
        ], 201);
    }

    /**
     * Get community activities for the building
     */
    public function index(Request $request)
    {
        $rules = [
            'search' => 'nullable|string',
            'count'  => 'nullable|integer|min:1',
            'page'   => 'nullable|integer|min:1',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->first()], 422);
        }

        $flat = AuthHelper::flat();
        if (!$flat) {
            return response()->json(['error' => 'Flat not assigned'], 404);
        }

        $user = Auth::user();
        $search = $request->get('search', '');
        $count = $request->get('count');
        $page = $request->get('page', 1);

        $query = CommunityActivity::where('building_id', $flat->building_id)
            ->where(function ($q) use ($user) {
                $q->where('status', 'approved')
                  ->orWhere('user_id', $user->id);
            })
            ->with(['creator', 'responses']);

        if ($search) {
            $query->where('title', 'LIKE', "%{$search}%");
        }

        if ($count) {
            $total = $query->count();
            $lastPage = ceil($total / $count);
            $currentPage = max(1, min($page, $lastPage));

            $offset = ($currentPage - 1) * $count;
            $activities = $query->offset($offset)->limit($count)->get();
        } else {
            $activities = $query->get();
            $total = $activities->count();
            $currentPage = 1;
            $lastPage = 1;
        }

        // Add user response and slot info to each activity
        $activities = collect($activities)->map(function ($activity) use ($user) {
            $userResponse = $activity->responses()
                ->where('user_id', $user->id)
                ->first();

            return array_merge($activity->toArray(), [
                'user_response'  => $userResponse ? $userResponse->response : null,
                'filled_slots'   => $activity->filledSlotsCount(),
                'is_slot_full'   => $activity->isSlotFull(),
            ]);
        })->toArray();

        return response()->json([
            'activities'   => $activities,
            'total'        => $total,
            'current_page' => $currentPage,
            'last_page'    => $lastPage,
        ], 200);
    }

    /**
     * Get single activity detail
     */
    public function show(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $flat = AuthHelper::flat();
        if (!$flat) {
            return response()->json(['error' => 'Flat not assigned'], 404);
        }

        $user = Auth::user();

        $activity = CommunityActivity::where('id', $request->id)
            ->where('building_id', $flat->building_id)
            ->where(function ($q) use ($user) {
                $q->where('status', 'approved')
                  ->orWhere('user_id', $user->id);
            })
            ->with(['creator', 'responses'])
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'Activity not found or not visible'], 404);
        }

        $userResponse = $activity->responses()
            ->where('user_id', $user->id)
            ->first();

        $data = array_merge($activity->toArray(), [
            'user_response' => $userResponse ? $userResponse->response : null,
            'filled_slots'  => $activity->filledSlotsCount(),
            'is_slot_full'  => $activity->isSlotFull(),
        ]);

        return response()->json(['activity' => $data], 200);
    }

    /**
     * Submit or change a response to an activity
     */
    public function respond(Request $request)
    {
        $rules = [
            'activity_id' => 'required|integer',
            'response'    => 'required|in:interested,not_interested,attending,maybe,not_attending',
        ];

        $validation = \Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->first()], 422);
        }

        $flat = AuthHelper::flat();
        if (!$flat) {
            return response()->json(['error' => 'Flat not assigned'], 404);
        }

        $user = Auth::user();

        $activity = CommunityActivity::where('id', $request->activity_id)
            ->where('building_id', $flat->building_id)
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'Activity not found'], 404);
        }

        // Validate response type matches activity's response_type
        $validResponses = [
            'simple'   => ['interested', 'not_interested'],
            'detailed' => ['attending', 'maybe', 'not_attending'],
        ];

        if (!in_array($request->response, $validResponses[$activity->response_type])) {
            return response()->json(['error' => 'Invalid response type for this activity'], 422);
        }

        DB::beginTransaction();
        try {
            // Find existing response and delete it (frees slot)
            $existingResponse = CommunityActivityResponse::where('community_activity_id', $activity->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingResponse) {
                $existingResponse->delete();
            }

            // Check if new response is a slot-filler
            $slotFillers = CommunityActivity::SLOT_FILLERS[$activity->response_type];
            $isSlotFiller = in_array($request->response, $slotFillers);

            if ($isSlotFiller && $activity->post_type === 'slot') {
                // Re-count filled slots
                $filledCount = $activity->filledSlotsCount();
                if ($filledCount >= $activity->max_slots) {
                    DB::rollBack();
                    return response()->json(['error' => 'Activity is full'], 422);
                }
            }

            // Create new response
            $newResponse = CommunityActivityResponse::create([
                'community_activity_id' => $activity->id,
                'user_id'               => $user->id,
                'flat_id'               => $flat->id,
                'response'              => $request->response,
            ]);

            DB::commit();

            return response()->json([
                'message'  => 'Response submitted successfully',
                'response' => $newResponse,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Activity respond error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to submit response'], 500);
        }
    }

    /**
     * Delete an activity (only owner can delete)
     */
    public function delete(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $flat = AuthHelper::flat();
        if (!$flat) {
            return response()->json(['error' => 'Flat not assigned'], 404);
        }

        $user = Auth::user();

        $activity = CommunityActivity::where('id', $request->id)
            ->where('user_id', $user->id)
            ->where('building_id', $flat->building_id)
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'Activity not found or not owned by you'], 404);
        }

        $activity->delete();

        return response()->json(['message' => 'Activity deleted successfully'], 200);
    }

    /**
     * Get participation statistics for an activity
     */
    public function stats(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $flat = AuthHelper::flat();
        if (!$flat) {
            return response()->json(['error' => 'Flat not assigned'], 404);
        }

        $activity = CommunityActivity::where('id', $request->id)
            ->where('building_id', $flat->building_id)
            ->with('responses')
            ->first();

        if (!$activity) {
            return response()->json(['error' => 'Activity not found'], 404);
        }

        $responses = $activity->responses;
        $totalResponses = $responses->count();

        if ($totalResponses === 0) {
            return response()->json([
                'stats'            => [],
                'total_responses'  => 0,
            ], 200);
        }

        // Get all possible response options for this activity type
        $responseOptions = [
            'simple'   => ['interested', 'not_interested'],
            'detailed' => ['attending', 'maybe', 'not_attending'],
        ];

        $options = $responseOptions[$activity->response_type];
        $stats = [];

        foreach ($options as $option) {
            $count = $responses->where('response', $option)->count();
            $percentage = round(($count / $totalResponses) * 100, 1);

            $stats[] = [
                'response'   => $option,
                'count'      => $count,
                'percentage' => $percentage,
            ];
        }

        return response()->json([
            'stats'            => $stats,
            'total_responses'  => $totalResponses,
        ], 200);
    }
}
