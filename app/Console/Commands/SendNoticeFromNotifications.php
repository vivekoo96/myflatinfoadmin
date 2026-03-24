<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Noticeboard;
use App\Models\Notification as DatabaseNotification;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendNoticeFromNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notice:send-from-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send noticeboard notifications when notice from_time is reached';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();

        $notices = Noticeboard::whereNull('from_notified_at')
            ->whereNotNull('from_time')
            ->where('from_time', '<=', $now->toDateTimeString())
            ->where('to_time', '>=', $now->toDateTimeString())
            ->with('blocks')
            ->get();

        foreach ($notices as $notice) {
            try {
                $building = $notice->building;
                if (!$building) continue;

                // Determine target block IDs from notice->blocks relation
                $blockIds = $notice->blocks->pluck('id')->toArray();
                if (empty($blockIds)) {
                    // If no blocks attached, default to all active blocks
                    $blockIds = $building->blocks()->where('status','Active')->pluck('id')->toArray();
                }

                // Get flats in those blocks (only active/sold flats as per other logic)
                $flats = $building->flats()->where('sold_out','Yes')->whereIn('block_id', $blockIds)->with(['owner','tanent'])->get();

                // Determine whether this is a new send or an update (was previously notified)
                $wasPreviouslyNotified = false;
                try {
                    $wasPreviouslyNotified = DB::table('noticeboard_blocks')
                        ->where('noticeboard_id', $notice->id)
                        ->whereNotNull('notified_at')
                        ->exists();
                } catch (\Exception $e) {
                    // ignore DB check failures and fall back to notice flag
                }
                if (! $wasPreviouslyNotified) {
                    $wasPreviouslyNotified = ! is_null($notice->from_notified_at);
                }

                $title = $wasPreviouslyNotified ? "Update Notification" : "New Notice Added";
                $body = $wasPreviouslyNotified
                    ? ('Notice "' . $notice->title . '" has been updated.')
                    : ('A new notice titled ' . $notice->title . ' has been posted.');
                // `A new notice titled "Exam Schedule Update" has been posted.`

                foreach ($flats as $flat) {
                    $usersToNotify = collect([$flat->owner, $flat->tanent])->filter();
                    foreach ($usersToNotify as $targetUser) {
                        if (!$targetUser) continue;

                        $dataPayload = [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'screen' => 'Timeline',
                            'params' => json_encode([
                                'ScreenTab' => 'Notice Board',
                                'noticeboardId' => (string) $notice->id,
                                'flat_id' => $flat->id,
                                'user_id' => $targetUser->id,
                                'building_id' => $building->id,
                            ]),
                            'categoryId' => '',
                            'channelId' => '',
                            'sound' => 'bellnotificationsound.wav',
                            'type' => 'NOTICEBOARD_FROM',
                            'user_id' => (string) $targetUser->id,
                        ];

                        // Persist DB notification
                        // try {
                        //     $notification = new DatabaseNotification();
                        //     $notification->user_id = $targetUser->id;
                        //     $notification->from_id = null;
                        //     $notification->flat_id = $flat->id;
                        //     $notification->building_id = $building->id;
                        //     $notification->title = $title;
                        //     $notification->body = $body;
                        //     $notification->type = 'noticeboard_from';
                        //     $notification->dataPayload = $dataPayload;
                        //     $notification->status = 0;
                        //     $notification->save();
                        // } catch (\Exception $ex) {
                        //     Log::error('Failed to save DB notification for notice '.$notice->id.' user '.$targetUser->id.': '.$ex->getMessage());
                        // }

                        // Send push
                        try {
                            NotificationHelper::sendNotification(
                                $targetUser->id,
                                $title,
                                $body,
                                $dataPayload,
                                [
                                    'from_id' => null,
                                    'flat_id' => $flat->id,
                                    'building_id' => $building->id,
                                    'type' => 'noticeboard_from',
                                    'ios_sound' => 'default'
                                ]
                            );
                        } catch (\Exception $e) {
                            Log::error('Push send failed for notice '.$notice->id.' user '.$targetUser->id.': '.$e->getMessage());
                        }
                    }
                }

                $notice->from_notified_at = Carbon::now();
                $notice->save();

                $this->info('Sent from-time notifications for notice id: '.$notice->id);
            } catch (\Exception $e) {
                Log::error('Error sending from-time notification for notice '.$notice->id.': '.$e->getMessage());
            }
        }

        return 0;
    }
}
