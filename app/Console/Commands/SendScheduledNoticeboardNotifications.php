<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Noticeboard;
use App\Jobs\SendNoticeboardNotification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendScheduledNoticeboardNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'noticeboard:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled noticeboard notifications when from_time is reached';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $now = Carbon::now();
            
            Log::info('SendScheduledNoticeboardNotifications command started', [
                'current_time' => $now->format('Y-m-d H:i:s'),
            ]);

            // Find noticeboards where:
            // 1. from_time <= now (time to send has arrived)
            // 2. to_time >= now (notice is still active)
            // 3. from_notified_at is NULL (not yet sent)
            $noticeboards = Noticeboard::where('from_time', '<=', $now)
                ->where('to_time', '>=', $now)
                ->whereNull('from_notified_at')
                ->whereNotNull('deleted_at', false)
                ->get();

            $this->info("Found {$noticeboards->count()} notices to send");

            foreach ($noticeboards as $noticeboard) {
                try {
                    // Get all block IDs for this notice
                    $blockIds = $noticeboard->blocks()->pluck('block_id')->toArray();

                    if (empty($blockIds)) {
                        Log::warning('Noticeboard has no blocks, skipping', [
                            'noticeboard_id' => $noticeboard->id,
                        ]);
                        continue;
                    }

                    // Dispatch the notification job to send messages and update timestamps
                    // The job itself handles updating notified_at in pivot and from_notified_at in noticeboard
                    SendNoticeboardNotification::dispatch($noticeboard, $blockIds, null, true);

                    Log::info('Scheduled noticeboard notification dispatched', [
                        'noticeboard_id' => $noticeboard->id,
                        'title' => $noticeboard->title,
                        'block_count' => count($blockIds),
                        'from_time' => $noticeboard->from_time,
                    ]);

                    $this->info("✓ Sent notification for: {$noticeboard->title} (ID: {$noticeboard->id})");
                } catch (\Exception $e) {
                    Log::error('Failed to send scheduled notification', [
                        'noticeboard_id' => $noticeboard->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $this->error("✗ Failed for notice ID {$noticeboard->id}: {$e->getMessage()}");
                }
            }

            Log::info('SendScheduledNoticeboardNotifications command completed', [
                'count' => $noticeboards->count(),
            ]);

            return 0;
        } catch (\Exception $e) {
            Log::error('SendScheduledNoticeboardNotifications command error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("Command failed: {$e->getMessage()}");
            return 1;
        }
    }
}
