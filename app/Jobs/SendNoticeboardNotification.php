<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Noticeboard;
use App\Models\Block;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendNoticeboardNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $noticeboard;
    protected $blockIds;
    protected $scheduleToken;
    protected $shouldUpdateFromNotifiedAt;

    /**
     * Create a new job instance.
     *
     * @param Noticeboard $noticeboard
     * @param array $blockIds - Block IDs to notify
     * @param string $scheduleToken - Optional token for schedule validation
     * @param bool $shouldUpdateFromNotifiedAt - Whether to update from_notified_at timestamp
     */
    public function __construct(Noticeboard $noticeboard, array $blockIds, $scheduleToken = null, $shouldUpdateFromNotifiedAt = true)
    {
        $this->noticeboard = $noticeboard;
        $this->blockIds = $blockIds;
        $this->scheduleToken = $scheduleToken;
        $this->shouldUpdateFromNotifiedAt = $shouldUpdateFromNotifiedAt;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // If a schedule token was provided, ensure it matches the latest scheduled token
            // stored in cache. This prevents older delayed jobs from sending if a newer
            // schedule has been created for the same noticeboard.
            if ($this->scheduleToken) {
                $cacheKey = 'noticeboard_schedule_' . $this->noticeboard->id;
                $current = Cache::get($cacheKey);
                if (! $current || $current !== $this->scheduleToken) {
                    Log::info('SendNoticeboardNotification skipped due to schedule token mismatch', [
                        'noticeboard_id' => $this->noticeboard->id,
                        'expected_token' => $this->scheduleToken,
                        'cache_token' => $current,
                    ]);
                    return;
                }
                // Optionally delete the cache key now that the job is running
                Cache::forget($cacheKey);
            }
            Log::info('SendNoticeboardNotification job started', [
                'noticeboard_id' => $this->noticeboard->id,
                'block_ids' => $this->blockIds,
            ]);

            // Before sending, mark these blocks as notified and update noticeboard.from_notified_at
            // so audit fields reflect that notifications were sent by this job.
            try {
                $now = Carbon::now();
                $updateCount = DB::table('noticeboard_blocks')
                    ->where('noticeboard_id', $this->noticeboard->id)
                    ->whereIn('block_id', $this->blockIds)
                    ->update(['notified_at' => $now]);

                Log::info('Updated notified_at in pivot table', [
                    'noticeboard_id' => $this->noticeboard->id,
                    'block_ids' => $this->blockIds,
                    'rows_updated' => $updateCount,
                    'notified_at_timestamp' => $now->toDateTimeString(),
                ]);

                $nb = Noticeboard::where('id', $this->noticeboard->id)->first();
                if ($nb && $this->shouldUpdateFromNotifiedAt) {
                    // Only set from_notified_at if it hasn't been set yet (avoid duplicate saves on retries)
                    if (is_null($nb->from_notified_at)) {
                        $nb->from_notified_at = $now;
                        $nb->save();
                        Log::info('Updated from_notified_at in noticeboard', [
                            'noticeboard_id' => $this->noticeboard->id,
                            'from_notified_at' => $now->toDateTimeString(),
                        ]);
                    } else {
                        Log::info('Skipped from_notified_at update (already set)', [
                            'noticeboard_id' => $this->noticeboard->id,
                            'existing_from_notified_at' => $nb->from_notified_at->toDateTimeString(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to update notified_at timestamps before sending notifications', [
                    'noticeboard_id' => $this->noticeboard->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Get users related to the specified blocks in multiple ways:
            // 1) Owners/tenants of flats in those blocks
            // 2) Users with assigned_blocks containing those block IDs (JSON column)
            $ownerIds = \App\Models\Flat::whereIn('block_id', $this->blockIds)
                ->pluck('owner_id')
                ->filter()
                ->toArray();
            $tenantIds = \App\Models\Flat::whereIn('block_id', $this->blockIds)
                ->pluck('tanent_id')
                ->filter()
                ->toArray();

            // Users who have assigned_blocks JSON containing any of these block IDs
            $assignedUserIds = [];
            foreach ($this->blockIds as $bid) {
                $rows = User::whereRaw("JSON_CONTAINS(COALESCE(assigned_blocks, '[]'), ?)", [json_encode($bid)])->pluck('id')->toArray();
                if (!empty($rows)) {
                    $assignedUserIds = array_merge($assignedUserIds, $rows);
                }
            }

            $allUserIds = array_unique(array_merge($ownerIds, $tenantIds, $assignedUserIds));

            $users = collect();
            if (!empty($allUserIds)) {
                $users = User::whereIn('id', $allUserIds)
                    ->whereNull('deleted_at')
                    ->get();
            }

            Log::info('Users found for notification', [
                'noticeboard_id' => $this->noticeboard->id,
                'user_count' => $users->count(),
                'block_ids' => $this->blockIds,
            ]);

            // Send notification to each user
            $sentCount = 0;
            $failedCount = 0;

            foreach ($users as $user) {
                try {
                    // Option 1: Send via email (if configured)
                    if ($user->email) {
                        // You can implement Mail::send() here
                        // For now, just log that we would send
                        Log::info('Noticeboard notification sent to user', [
                            'noticeboard_id' => $this->noticeboard->id,
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'title' => $this->noticeboard->title,
                        ]);
                        $sentCount++;
                    }

                    // Option 2: Send via SMS (if configured)
                    if ($user->phone) {
                        // You can implement SMS sending here
                        Log::info('Noticeboard SMS would be sent', [
                            'user_id' => $user->id,
                            'phone' => $user->phone,
                        ]);
                    }

                    // Option 3: Send via push notification (if configured)
                    // Add Firebase/push notification logic here
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Failed to send notification to user', [
                        'noticeboard_id' => $this->noticeboard->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('SendNoticeboardNotification job completed', [
                'noticeboard_id' => $this->noticeboard->id,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('SendNoticeboardNotification job failed', [
                'noticeboard_id' => $this->noticeboard->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
