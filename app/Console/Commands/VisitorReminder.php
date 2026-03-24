<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Visitor;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use Carbon\Carbon;
use Log;

class VisitorReminder extends Command
{
    protected $signature = 'visitor:reminder';
    protected $description = 'Send reminder to user to extend visitor stay time when stay_to time reaches';

    public function handle()
    {
        Log::info('VisitorReminder command started');

        // $now = Carbon::now();
        // $oneMinuteAgo = $now->copy()->subMinute();
        // $oneMinuteAhead = $now->copy()->addMinute(10);

        // // 🔍 Find visitors whose stay_to time is now (within 1 min range)
        // $visitors = Visitor::whereBetween('stay_to', [$oneMinuteAgo, $oneMinuteAhead])
        //     ->where('status', 'Living')
        //     ->get();
        
        
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
            
            // $visitor->status = 'Time out';
            // $visitor->save();

            Log::info("Sending stay reminder for Visitor {$visitor->id}", [
                'flat_id' => $flat->id,
                'user_id' => $user->id,
                'stay_to' => $visitor->stay_to,
            ]);

            // 🔔 Notification content
            $title = 'Extend Your Visitor Stay >';
            $body = 'Your visitor’s stay time has ended. Please extend the time if they are still visiting.';

            $categoryId = $visitor->type == 'Planned' ? 'PlannedVisitors' : 'UnplannedVisitors';

            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'screen' => 'Visitors',
                'params' => json_encode([
                    'visitor_id' => $visitor->id,
                    'flat_id' => $flat->id,
                    'user_id'=>$user->id
                ]),
                'categoryId' => $categoryId,
                'channelId' => 'longring',
                'sound' => 'longring.wav',
                'type' => 'VISITOR_STAY_END',
                'actionButtons' => json_encode(["Extend Stay", "Mark as Left"]),
            ];

            // 🚀 Send notification to the correct user
            NotificationHelper::sendNotification(
                $user->id,
                $title,
                $body,
                $dataPayload
            );
        }

        Log::info('VisitorReminder command finished');
    }
}
