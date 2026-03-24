<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Visitor;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use Carbon\Carbon;
use Log;

class VisitorSecurityReminder extends Command
{
    protected $signature = 'visitor:security-reminder';
    protected $description = 'Notify security when a visitor’s stay time ends or after 10 minutes';

   public function handle()
    {
        Log::info('VisitorReminder command started');

$now = Carbon::now();
$tenMinutesAgo = $now->copy()->subMinutes(10);
$range = 30; // seconds tolerance

$visitors = Visitor::where('status', 'Living')
    ->where(function ($query) use ($now, $tenMinutesAgo, $range) {
        // Stay ended now (within 30s)
        $query->whereBetween('stay_to', [$now->copy()->subSeconds($range), $now->copy()->addSeconds($range)])
              // Stay ended 10 minutes ago (within 30s)
              ->orWhereBetween('stay_to', [$tenMinutesAgo->copy()->subSeconds($range), $tenMinutesAgo->copy()->addSeconds($range)]);
    })
    ->get();


        if ($visitors->isEmpty()) {
            Log::info('No visitors with matching stay_to time.');
            return;
        }

        foreach ($visitors as $visitor) {
            $flat = $visitor->flat;
            if (!$flat) {
                Log::warning("Visitor {$visitor->id} has no flat linked");
                continue;
            }

            // 🧍 Identify which user should get the notification
            $user = ($flat->living_status == 'Owner' || $flat->living_status == 'Vacant')
                ? $flat->owner
                : $flat->tanent;

            if (!$user) {
                Log::warning("Flat {$flat->id} has no associated user");
                continue;
            }
            
            // $visitor->over_stay_count = $visitor->over_stay_count+1
            // $visitor->save();
            
            $visitor->over_stay_count = $visitor->over_stay_count + 1;
            $visitor->save();


            Log::info("Sending stay reminder for Visitor {$visitor->id}", [
                'flat_id' => $flat->id,
                'user_id' => $user->id,
                'stay_to' => $visitor->stay_to,
            ]);

            // 🔔 Notification content
            $title = 'Visitor Stay Limit Reached';
            $body = 'Visitor '.$visitor->head_name.' for '.$flat->name.' has reached the allowed stay limit. It is now checkout time. Please take necessary action.';
            //   $body = "Visitor $visitor_name for Flat $flat->name has reached the allowed stay limit. It is now checkout time. Please take necessary action.";

            $categoryId = $visitor->type == 'Planned' ? 'PlannedVisitors' : 'UnplannedVisitors';

            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'SelectedOngoingVisitor',
                'params' => json_encode([
                    //   'item' => $flat,
                    'visitor_id' => $visitor->id,
                    'flat_id' => $flat->id,
                    'user_id'=>$user->id
                ]),
                // 'categoryId' => $categoryId,
                'categoryId' => 'GatePassUpdate',
                'channelId' => 'GatePass',
                'sound' => 'bellnotificationsound.wav',
                'type' => 'GATEPASS_APPROVED_EXTRA_ITEM',
            ];

            // 🚀 Send notification to the correct user
            NotificationHelper::sendNotification(
                $visitor->security_id,
                $title,
                $body,
                $dataPayload,
                 [
                'from_id' => $visitor->security_id,  // From the person accepting
                'flat_id' => $flat->id,
                'building_id' => $flat->building_id,
                'type' => 'issue_accepted',
                'apns_client' => $this->apnsClient ?? null,
                'ios_sound' => 'longring.wav'
            ]
            );
        }

        Log::info('VisitorReminder command finished');
    }
}
