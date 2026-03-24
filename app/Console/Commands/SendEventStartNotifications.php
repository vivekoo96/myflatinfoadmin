<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\Building;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendEventStartNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-start-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications when an active event reaches its start date/time';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();

        // Select events that are Active, not yet notified for start, and whose start time is <= now
        $events = Event::where('status', 'Active')
            ->whereNull('start_notified_at')
            ->whereNotNull('from_time')
            ->where(function($q) use ($now) {
                $q->where('from_time', '<=', $now->toDateTimeString());
            })
            ->get();

        foreach ($events as $event) {
            try {
                $building = Building::find($event->building_id);
                if (!$building) continue;

                $title = 'New Event Posted : '. $event->name;
                $body = 'A new event has been added by the admin. Please view the details and participate as you wish.';

                foreach ($building->flats as $flat) {
                    $tenant = $flat->tanent;
                    $owner = $flat->owner;
                    $usersToNotify = collect([$tenant, $owner])->filter();

                    foreach ($usersToNotify as $targetUser) {
                        if (!$targetUser) continue;

                        $dataPayload = [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'screen' => 'Timeline',
                            'params' => json_encode([
                                'ScreenTab' => 'Events',
                                'event_id' => $event->id,
                                'flat_id' => $flat->id,
                                'user_id' => $targetUser->id,
                                'building_id' => $building->id,
                            ]),
                            'categoryId' => '',
                            'channelId' => '',
                            'sound' => 'bellnotificationsound.wav',
                            'type' => 'EVENT_STARTING',
                            'user_id' => (string) $targetUser->id,
                        ];

                        NotificationHelper::sendNotification(
                            $targetUser->id,
                            $title,
                            $body,
                            $dataPayload,
                            [
                                'from_id' => $targetUser->id,
                                'flat_id' => $flat->id,
                                'building_id' => $flat->building_id,
                                'type' => 'event_starting',
                                'ios_sound' => 'default'
                            ],
                            ['user']
                        );
                    }
                }

                $event->start_notified_at = Carbon::now();
                $event->save();

                $this->info('Notified start for event id: ' . $event->id);
            } catch (\Exception $e) {
                Log::error('Error sending start notification for event '.$event->id.': '.$e->getMessage());
            }
        }

        return 0;
    }
}
