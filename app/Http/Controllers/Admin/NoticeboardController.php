<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Noticeboard;
use App\Models\Building;
use App\Models\Flat;
use App\Models\Notification as DatabaseNotification;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class NoticeboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->role == 'BA' || Auth::user()->selectedRole->name == "President"|| Auth::user()->hasPermission('custom.noticeboards')) {
            // allowed
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        // Resolve the correct building context for the current user
        $building = $this->getCurrentBuilding();
        if ($building) {
            // Eager load noticeboards and their blocks/building to avoid missing relations in views
            $building->load(['noticeboards.blocks', 'noticeboards.building']);
        } else {
            // Provide an empty placeholder to avoid errors in views — prevents null access
            $building = new Building();
            $building->setRelation('noticeboards', collect());
        }
        try {
            Log::info('Noticeboard index loaded', ['building_id' => $building->id, 'noticeboards_count' => $building->noticeboards->count()]);
        } catch (\Exception $e) {}
        return view('admin.noticeboard.index', compact('building'));
    }

    public function store(Request $request)
    {


        // dd($request->all());
        if (Auth::user()->role == 'BA' || Auth::user()->hasRole('president') || Auth::user()->hasPermission('custom.noticeboards')) {
            // allowed
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }

        // Load original notice (if editing) so we can allow unmodified past times to remain
        $originalNotice = null;
        if ($request->id) {
            $originalNotice = Noticeboard::withTrashed()->find($request->id);
        }

        // Build validation rules dynamically: when editing (request->id) skip strict date validation
        $rules = [
            'title' => 'required|string',
            'desc' => 'required|string',
            'block_ids' => 'required|array',
        ];

        if (! $request->id) {
            // Creation: enforce date validation with leeway
            $rules['from_time'] = ['required', 'date', function ($attribute, $value, $fail) use ($request, $originalNotice) {
                try {
                    $from = $this->parseClientDatetime($value);
                    // Allow a small leeway to account for client/server clock differences and timezone parsing
                    $leewaySeconds = 60; // tolerate up to 60 seconds of drift
                    $now = Carbon::now();
                    if ($from->lt($now->subSeconds($leewaySeconds))) {
                        $fail('From time cannot be in the past.');
                    }
                } catch (\Exception $e) {
                    $fail('Invalid from time.');
                }
            }];

            $rules['to_time'] = ['required', 'date', 'after_or_equal:from_time', function ($attribute, $value, $fail) use ($request, $originalNotice) {
                try {
                    $to = $this->parseClientDatetime($value);
                    // Allow small leeway for server/client clock differences
                    $leewaySeconds = 60;
                    $now = Carbon::now();
                    if ($to->lt($now->subSeconds($leewaySeconds))) {
                        $fail('To time cannot be in the past.');
                    }
                } catch (\Exception $e) {
                    $fail('Invalid to time.');
                }
            }];
        } else {
            // Editing: skip date validation entirely (admin requested no date validation on edit)
            // Allow any from_time/to_time submitted; validation will not block the update.
        }

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Get raw form data and parse datetime values properly
        $fromTimeRaw = $request->input('from_time');
        $toTimeRaw = $request->input('to_time');
        
        // Log the incoming datetime values for debugging
        try {
            Log::info('Noticeboard store: Incoming from_time/to_time (RAW)', [
                'from_time_raw' => $fromTimeRaw,
                'to_time_raw' => $toTimeRaw,
                'from_time_null' => is_null($fromTimeRaw),
                'to_time_null' => is_null($toTimeRaw),
                'from_time_empty' => empty($fromTimeRaw),
                'to_time_empty' => empty($toTimeRaw),
                'from_time_length' => strlen($fromTimeRaw),
                'to_time_length' => strlen($toTimeRaw),
            ]);
        } catch (\Exception $e) {}
        
        // Parse datetime values if they exist (convert from datetime-local format)
        $from_time = null;
        $to_time = null;
        
        if (!empty($fromTimeRaw)) {
            try {
                $from_time = $this->parseClientDatetime($fromTimeRaw);
                Log::info('Successfully parsed from_time', [
                    'raw' => $fromTimeRaw,
                    'parsed' => $from_time->toDateTimeString(),
                    'timezone' => $from_time->getTimezone()->getName(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to parse from_time', ['raw_value' => $fromTimeRaw, 'error' => $e->getMessage()]);
            }
        }
        
        if (!empty($toTimeRaw)) {
            try {
                $to_time = $this->parseClientDatetime($toTimeRaw);
                Log::info('Successfully parsed to_time', [
                    'raw' => $toTimeRaw,
                    'parsed' => $to_time->toDateTimeString(),
                    'timezone' => $to_time->getTimezone()->getName(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to parse to_time', ['raw_value' => $toTimeRaw, 'error' => $e->getMessage()]);
            }
        }
        
        // Prepare data array with parsed datetime values
        $data = [
            'title' => $request->input('title'),
            'desc' => $request->input('desc'),
            'status' => $request->input('status'),
        ];
        
        // Only add datetime if successfully parsed
        if ($from_time) {
            $data['from_time'] = $from_time;
        }
        if ($to_time) {
            $data['to_time'] = $to_time;
        }
        
        try {
            Log::info('Noticeboard store: Data prepared for save', [
                'has_from_time' => isset($data['from_time']),
                'has_to_time' => isset($data['to_time']),
                'from_time_value' => isset($data['from_time']) ? $data['from_time']->toDateTimeString() : 'NOT SET',
                'to_time_value' => isset($data['to_time']) ? $data['to_time']->toDateTimeString() : 'NOT SET',
            ]);
        } catch (\Exception $e) {}

        $currentBuilding = $this->getCurrentBuilding();
        // If no building is found for BA or normal users, require explicit building_id in request or show an error
        if (! $currentBuilding && $request->has('building_id') == false) {
            // allow SA or other users with building_id in request to pass through
            if (Auth::user()->role == 'BA') {
                return redirect()->back()->with('error', 'Could not determine your building. Please set a primary building in your profile or contact support.');
            }
        }

        if ($request->id) {
            $noticeboard = Noticeboard::where('id', $request->id)->withTrashed()->first();
            if (! $noticeboard) {
                return redirect()->back()->with('error', 'Notice not found');
            }
            
            // CAPTURE ORIGINAL STATE FOR CHANGE DETECTION
            $originalBlocks = $noticeboard->blocks()->pluck('id')->toArray();
            $originalTitle = $noticeboard->title;
            $originalDesc = $noticeboard->desc; 
            $originalFromTime = $noticeboard->from_time;
            $originalToTime = $noticeboard->to_time;
            $originalFromNotifiedAt = $noticeboard->from_notified_at;
            
            // Get blocks that have already been notified (using noticeboard_blocks.notified_at)
            $notifiedBlockIds = $noticeboard->blocks()
                ->whereNotNull('noticeboard_blocks.notified_at')
                ->pluck('block_id')
                ->toArray();
            
            $noticeboard->fill($data);
            // Explicitly set datetime values so DB does not default to current time
            if (isset($data['from_time'])) {
                $noticeboard->from_time = $data['from_time'] instanceof \DateTime ? $data['from_time']->format('Y-m-d H:i:s') : $data['from_time'];
                try { Log::info('Assigning from_time (update branch) to model before save', ['value' => $noticeboard->from_time]); } catch (\Exception $e) {}
            }
            if (isset($data['to_time'])) {
                $noticeboard->to_time = $data['to_time'] instanceof \DateTime ? $data['to_time']->format('Y-m-d H:i:s') : $data['to_time'];
                try { Log::info('Assigning to_time (update branch) to model before save', ['value' => $noticeboard->to_time]); } catch (\Exception $e) {}
            }
            // Keep the original raw block_ids selection if provided (so we can show "All Blocks" if admin selected 'all')
            if ($request->has('block_ids') && Schema::hasColumn('noticeboards', 'block_ids')) {
                $noticeboard->block_ids = json_encode($request->block_ids);
            }
            // ensure building_id is present, using resolved user building context
            if (empty($noticeboard->building_id)) {
                $currentBuilding = $this->getCurrentBuilding();
                if ($currentBuilding) {
                    $noticeboard->building_id = $currentBuilding->id;
                }
            }
            $noticeboard->save();
            try { 
                Log::info('Noticeboard saved (post): building_id', [
                    'noticeboard_id' => $noticeboard->id, 
                    'building_id' => $noticeboard->building_id,
                    'from_time_saved' => $noticeboard->from_time,
                    'to_time_saved' => $noticeboard->to_time,
                ]); 
            } catch (\Exception $e) {}
            $msg = 'Notice updated successfully.';
        } else {
            $noticeboard = new Noticeboard();
            $noticeboard->fill($data);
            // Explicitly set datetime values so DB does not default to current time
            if (isset($data['from_time'])) {
                $noticeboard->from_time = $data['from_time'] instanceof \DateTime ? $data['from_time']->format('Y-m-d H:i:s') : $data['from_time'];
                try { Log::info('Assigning from_time (create branch) to model before save', ['value' => $noticeboard->from_time]); } catch (\Exception $e) {}
            }
            if (isset($data['to_time'])) {
                $noticeboard->to_time = $data['to_time'] instanceof \DateTime ? $data['to_time']->format('Y-m-d H:i:s') : $data['to_time'];
                try { Log::info('Assigning to_time (create branch) to model before save', ['value' => $noticeboard->to_time]); } catch (\Exception $e) {}
            }
            // associate building from current user if model has building_id
            // store building id from the current user's context (supports both building_id and assigned buildings)
            $currentBuilding = $this->getCurrentBuilding();
            if ($currentBuilding) {
                $noticeboard->building_id = $currentBuilding->id;
            }
            // If not determined by user context, allow SA/president to inject a building_id
            if (empty($noticeboard->building_id) && $request->has('building_id') && (Auth::user()->role != 'BA')) {
                $noticeboard->building_id = $request->input('building_id');
            }
            // store raw selection of block ids (may contain 'all' as the original selection)
            if ($request->has('block_ids') && Schema::hasColumn('noticeboards', 'block_ids')) {
                $noticeboard->block_ids = json_encode($request->block_ids);
            }
            $noticeboard->save();
            try { Log::info('Noticeboard created (post): building_id', ['noticeboard_id' => $noticeboard->id, 'building_id' => $noticeboard->building_id]); } catch (\Exception $e) {}
            $msg = 'Notice created successfully.';
            
            // Mark this as a new notice (no original state to compare)
            $originalBlocks = [];
            $originalTitle = null;
            $originalDesc = null;
            $originalFromTime = null;
            $originalFromNotifiedAt = null;
            $notifiedBlockIds = [];
        }

        // Sync blocks
        $blockIds = $request->block_ids;
        // Sanitize incoming block ids to avoid empty strings or invalid values causing DB errors
        if (! is_array($blockIds)) {
            if (is_null($blockIds)) {
                $blockIds = [];
            } else {
                $blockIds = (array) $blockIds;
            }
        }
        // Remove empty values and normalize; preserve explicit 'all' marker
        $blockIds = array_values(array_filter($blockIds, function ($v) {
            if ($v === 'all') return true;
            if ($v === 0 || $v === '0') return true;
            return $v !== null && $v !== '' && is_numeric($v);
        }));

        // If no block ids provided (frontend removed selection), default to 'all'
        if (count($blockIds) === 0) {
            $blockIds = ['all'];
        }

        // Persist raw block_ids selection on the noticeboard model if column exists
        if (Schema::hasColumn('noticeboards', 'block_ids')) {
            try {
                $noticeboard->block_ids = json_encode($blockIds);
                $noticeboard->save();
            } catch (\Exception $e) {
                // non-fatal; continue — syncing below will handle pivot
            }
        }
        $isAll = false;
        if (is_array($blockIds) && count($blockIds) > 0) {
            // If 'all' selected, replace with active block ids
            if (in_array('all', $blockIds)) {
                $isAll = true;
                $currentBuilding = $this->getCurrentBuilding();
                $blockIds = $currentBuilding ? $currentBuilding->blocks()->where('status', 'Active')->pluck('id')->toArray() : [];
            } else {
                // If actual selected block count equals total active blocks, treat as "all"
                $currentBuilding = $this->getCurrentBuilding();
                $active = $currentBuilding ? $currentBuilding->blocks()->where('status', 'Active')->pluck('id')->toArray() : [];
                if (count($active) > 0 && count(array_diff($active, $blockIds)) == 0) {
                    $isAll = true;
                }
            }
            // Use syncWithoutDetaching to preserve existing notified_at pivot data when syncing blocks
            $noticeboard->blocks()->syncWithoutDetaching($blockIds);
            // Log details about the saved notice and synced blocks — helps debug why items might not show in UI
            try {
                Log::info('Noticeboard saved and blocks synced', [
                    'noticeboard_id' => $noticeboard->id,
                    'building_id' => $noticeboard->building_id,
                    'block_ids' => $blockIds,
                    'is_all' => $isAll,
                ]);
            } catch (\Exception $e) {
                // non-fatal — logging may fail if disk or logger config issues
            }
        }

        // Persist is_all_blocks when the column exists
        if (Schema::hasColumn('noticeboards', 'is_all_blocks')) {
            $noticeboard->is_all_blocks = $isAll;
            $noticeboard->save();
        }

        // ===== SMART NOTIFICATION LOGIC FOR UPDATES =====
        if ($request->id && $originalTitle !== null) {
            // This is an UPDATE (not a create)
            $titleOrDescChanged = ($originalTitle !== $noticeboard->title || $originalDesc !== $noticeboard->desc);
            // Determine newly added and removed blocks first so we can detect changes
            $newBlocks = array_diff($blockIds, $originalBlocks);
            $removedBlocks = array_diff($originalBlocks, $blockIds);
            // Treat blocks as changed only when there are additions or removals (order-insensitive)
            $blocksChanged = (count($newBlocks) > 0 || count($removedBlocks) > 0);
            $fromTimeChanged = ($originalFromTime !== $noticeboard->from_time);
            $toTimeChanged = ($originalToTime !== $noticeboard->to_time);
            
            $notifyBlocks = [];
            $shouldUpdateFromNotifiedAt = false;
            $notificationMessage = '';
            
            // CASE 1: Content (title or description) changed
            if ($titleOrDescChanged) {
                // Send notification to ALL current blocks immediately
                $notifyBlocks = $blockIds;
                $shouldUpdateFromNotifiedAt = true;
                $notificationMessage = 'Content updated. Notifications sent to all blocks.';
            }
            // CASE 2: From time changed to future
            elseif ($fromTimeChanged) {
                $newFrom = Carbon::parse($noticeboard->from_time);
                if ($newFrom->greaterThan(Carbon::now())) {
                    // Clear notification flags for future time
                    $noticeboard->from_notified_at = null;
                    // Clear notified_at timestamps for all blocks
                    DB::table('noticeboard_blocks')
                        ->where('noticeboard_id', $noticeboard->id)
                        ->update(['notified_at' => null]);
                    $notificationMessage = 'From time changed to future. Notifications will be sent at the new from_time.';
                    Log::info('Noticeboard update: From time changed to future - clear notification flags', [
                        'noticeboard_id' => $noticeboard->id,
                    ]);
                    // Also schedule a delayed notification job for the new from_time to ensure
                    // users receive the notice at the updated time even if scheduler timing varies.
                    try {
                        // Expand 'all' to actual active block IDs for this building
                        $scheduleBlockIds = $blockIds;
                        if (in_array('all', $scheduleBlockIds)) {
                            $currBuilding = $this->getCurrentBuilding();
                            $scheduleBlockIds = $currBuilding ? $currBuilding->blocks()->where('status', 'Active')->pluck('id')->toArray() : [];
                        }
                        if (!empty($scheduleBlockIds)) {
                            // Create a schedule token and store in cache so earlier scheduled jobs
                            // can be ignored when a newer schedule replaces them.
                            $token = uniqid('nb_', true);
                            $cacheKey = 'noticeboard_schedule_' . $noticeboard->id;
                            // Time to keep token in cache: seconds until newFrom + 1 minute buffer
                            $seconds = Carbon::now()->diffInSeconds($newFrom) + 60;
                            if ($seconds < 0) { $seconds = 60; }
                            try {
                                \Illuminate\Support\Facades\Cache::put($cacheKey, $token, $seconds);
                            } catch (\Exception $e) {
                                // ignore cache failures but proceed to dispatch
                            }

                            \App\Jobs\SendNoticeboardNotification::dispatch($noticeboard, $scheduleBlockIds, $token)->delay($newFrom);
                            Log::info('Scheduled delayed noticeboard notification', [
                                'noticeboard_id' => $noticeboard->id,
                                'scheduled_for' => $newFrom->toDateTimeString(),
                                'block_ids' => $scheduleBlockIds,
                                'token' => $token,
                            ]);
                        } else {
                            Log::warning('No blocks found when scheduling delayed notification', ['noticeboard_id' => $noticeboard->id]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to schedule delayed noticeboard notification', [
                            'noticeboard_id' => $noticeboard->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            // CASE 3: New blocks added
            elseif ($blocksChanged && count($newBlocks) > 0 && $originalFromNotifiedAt) {
                // Notice was already sent; notify only new blocks immediately
                $notifyBlocks = $newBlocks;
                $shouldUpdateFromNotifiedAt = true;
                $notificationMessage = 'New block(s) added. Notifications sent to newly added blocks.';
            }
            // CASE X: To time extended while notice is active (from_time already passed)
            elseif (! $fromTimeChanged && $toTimeChanged) {
                $now = Carbon::now();
                $origFrom = Carbon::parse($originalFromTime);
                $currFrom = Carbon::parse($noticeboard->from_time);
                // If the notice had already started (from_time <= now) and still started now,
                // treat the to_time extension as a content update and notify all current blocks.
                if ($origFrom->lessThanOrEqualTo($now) && $currFrom->lessThanOrEqualTo($now)) {
                    $notifyBlocks = $blockIds;
                    $shouldUpdateFromNotifiedAt = true;
                    $notificationMessage = 'To time extended while notice active. Notifications sent to all blocks.';
                }
            }
            // CASE 4: Blocks removed (no notification)
            elseif ($blocksChanged && count($removedBlocks) > 0) {
                // No notification needed for removed blocks
                $notificationMessage = 'Block(s) removed. No notifications sent.';
            }
            // CASE 5: No changes detected
            else {
                $notificationMessage = 'Notice updated.';
            }
            
            // SEND NOTIFICATIONS IF NEEDED
            if (count($notifyBlocks) > 0) {
                try {
                    $building = $this->getCurrentBuilding();
                    
                    // Get all flats in the target blocks
                    $flats = Flat::whereIn('block_id', $notifyBlocks)
                        ->where('building_id', $building->id)
                        ->where(function($query) {
                            $query->whereNotNull('owner_id')
                                  ->orWhereNotNull('tanent_id');
                        })
                        ->with(['owner', 'tanent'])
                        ->get();

                      $wasPreviouslyNotified = $originalFromNotifiedAt || !empty($notifiedBlockIds);
                    $title = $wasPreviouslyNotified ? "Update Notification" : "New Notice";
                    $body = 'Notice "' . $noticeboard->title . '" has been ' . ($wasPreviouslyNotified ? 'updated' : 'created') . '.';

                    $dataPayloadBase = [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'screen' => 'Timeline',
                        'params' => json_encode([
                            'ScreenTab' => 'Notice Board',
                            'noticeboardId' => (string) $noticeboard->id,
                            'building_id' => (string) $building->id,
                        ]),
                         'title' => $title,
                        'body' => $body,
                        'categoryId' => '',
                        'channelId' => '',
                        'sound' => 'bellnotificationsound.wav',
                        'type' => 'NOTICEBOARD_UPDATED',
                        'noticeboard_id' => (string) $noticeboard->id,
                    ];

                    $sentCount = 0;
                    foreach ($flats as $flat) {
                        $usersToNotify = collect([$flat->owner, $flat->tanent])->filter();
                        foreach ($usersToNotify as $targetUser) {
                            if (!$targetUser) continue;

                            try {
                                $dataPayload = $dataPayloadBase;
                                $dataPayload['user_id'] = (string) $targetUser->id;
                                $dataPayload['flat_id'] = (string) $flat->id;

                                NotificationHelper::sendNotification(
                                    $targetUser->id,
                                    $title,
                                    $body,
                                    $dataPayload,
                                    [
                                        'from_id' => null,
                                        'flat_id' => $flat->id,
                                        'building_id' => $building->id,
                                        'type' => 'noticeboard_updated',
                                        'ios_sound' => 'default'
                                    ],['user']
                                );
                                $sentCount++;
                            } catch (\Exception $e) {
                                Log::error('Failed to send notification to user', [
                                    'user_id' => $targetUser->id,
                                    'noticeboard_id' => $noticeboard->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }

                    Log::info('Noticeboard notifications sent', [
                        'noticeboard_id' => $noticeboard->id,
                        'notify_blocks' => $notifyBlocks,
                        'sent_count' => $sentCount,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send noticeboard notifications', [
                        'noticeboard_id' => $noticeboard->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
            
            $noticeboard->save();
            $msg = 'Notice updated successfully. ' . $notificationMessage;
        } else {
            // New notice creation - notifications handled by scheduler at from_time
            $msg = 'Notice created successfully. Notifications will be sent by the scheduler at the notice from_time.';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Determine the most appropriate building for current user context.
     * Priority order:
     *  - $user->building (belongsTo) — used if present
     *  - $user->building_id attribute — direct id for older schema
     *  - $user->buildings() collection — first building if assigned
     */
    private function getCurrentBuilding()
    {
        $user = Auth::user();
        if (! $user) return null;

        if ($user->building) {
            try { Log::info('Resolved building from user->building', ['user_id' => $user->id, 'building_id' => $user->building->id]); } catch (\Exception $e) {}
            return $user->building;
        }

        if (!empty($user->building_id)) {
            $b = Building::where('id', $user->building_id)->first();
            if ($b) {
                try { Log::info('Resolved building from user->building_id', ['user_id' => $user->id, 'building_id' => $b->id]); } catch (\Exception $e) {}
                return $b;
            }
        }

        // As a fallback, use the first building the user is assigned to if available
        // Prefer buildings that include department assignments
        $assigned = method_exists($user, 'allBuildings') ? $user->allBuildings() : $user->buildings();
        if ($assigned && is_iterable($assigned) && count($assigned) > 0) {
            try { Log::info('Resolved building from user->buildings()', ['user_id' => $user->id, 'building_id' => $assigned[0]->id]); } catch (\Exception $e) {}
            return $assigned[0];
        }
        try { Log::warning('Unable to resolve building for user', ['user_id' => $user->id]); } catch (\Exception $e) {}
        return null;
    }

    public function show($id)
    {
        if (Auth::user()->role == 'BA' || Auth::user()->hasRole('president') || Auth::user()->hasPermission('custom.noticeboards')) {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }
        $noticeboard = Noticeboard::where('id', $id)->withTrashed()->first();
        if (! $noticeboard) {
            return redirect()->route('noticeboard.index');
        }
        return view('admin.noticeboard.show', compact('noticeboard'));
    }

    /**
     * Parse datetime values coming from HTML `datetime-local` inputs and other formats
     * into a Carbon instance using the application timezone.
     */
    private function parseClientDatetime($value)
    {
        // Trim and normalize
        $v = trim($value);
        // HTML datetime-local: 'YYYY-MM-DDTHH:MM' or 'YYYY-MM-DDTHH:MM:SS'
        try {
            if (strpos($v, 'T') !== false) {
                // try seconds first, then fallback to minute precision
                if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $v)) {
                    return Carbon::createFromFormat('Y-m-d\TH:i:s', $v, config('app.timezone'));
                }
                return Carbon::createFromFormat('Y-m-d\TH:i', $v, config('app.timezone'));
            }
            // else let Carbon parse
            return Carbon::parse($v, config('app.timezone'));
        } catch (\Exception $e) {
            return Carbon::parse($v, config('app.timezone'));
        }
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id, Request $request)
    {
        if (Auth::user()->role == 'BA' || Auth::user()->hasRole('president') || Auth::user()->hasPermission('custom.vehicles')) {
            //
        } else {
            return redirect('permission-denied')->with('error', 'Permission denied!');
        }
        $noticeboard = Noticeboard::where('id', $id)->withTrashed()->first();
        if ($request->action == 'delete') {
            $noticeboard->delete();
        } else {
            $noticeboard->restore();
        }
        return response()->json([
            'msg' => 'success'
        ], 200);
    }
}

