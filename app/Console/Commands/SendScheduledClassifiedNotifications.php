<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Classified;
use App\Models\Flat;
use App\Models\ClassifiedBuilding;
use App\Models\Building;
use App\Models\Notification as DatabaseNotification;
use App\Helpers\NotificationHelper2 as NotificationHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendScheduledClassifiedNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'classified:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled classified notifications for approved classifieds';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();

        // Find approved classifieds that haven't been notified yet
        // Only get classifieds where is_notified is false or null (not yet sent)
        $classifieds = Classified::where('status', 'Approved')
            ->where('is_approved_on_creation', true)
            ->where(function($query) {
                $query->whereNull('is_notified')
                      ->orWhere('is_notified', false);
            })
            ->whereNull('deleted_at')
            ->get();

        foreach ($classifieds as $classified) {
            try {
                $targetUsers = collect();

                if($classified->category == 'All Buildings') {
                    // Send to users only in buildings associated with this classified
                    $buildingIds = ClassifiedBuilding::where('classified_id', $classified->id)->pluck('building_id')->toArray();
                    $allowedBuildingIds = Building::whereIn('id', $buildingIds)
                        ->whereHas('permissions', function($q) {
                            $q->where('name', 'Classified for all buildings');
                        })->pluck('id')->toArray();
                    if (count($allowedBuildingIds) > 0) {
                        $flats = Flat::whereIn('building_id', $allowedBuildingIds)
                            ->where(function($query) {
                                $query->whereNotNull('owner_id')
                                      ->orWhereNotNull('tanent_id');
                            })->get();

                        foreach ($flats as $flat) {
                            if ($flat->owner) {
                                $targetUsers->push($flat->owner);
                            }
                            if ($flat->tanent) {
                                $targetUsers->push($flat->tanent);
                            }
                        }
                    }
                } 
                if($classified->category == 'Within Building') {
                    // Within Building
                    $building = Building::find($classified->building_id);
                    if ($building && $building->hasPermission('Classified for withinbuilding')) {
                        if ($classified->block_id == 0) {
                            // All blocks in current building
                            $flats = Flat::where('building_id', $building->id)
                                ->where(function($query) {
                                    $query->whereNotNull('owner_id')
                                          ->orWhereNotNull('tanent_id');
                                })->get();
                                  Log::info('No target users for classified', ['flats' => $flats]);
                        } else {
                            // Specific block
                            $flats = Flat::where('block_id', $classified->block_id)
                                ->where(function($query) {
                                    $query->whereNotNull('owner_id')
                                          ->orWhereNotNull('tanent_id');
                                })->get();
                                 Log::info('No target users for classified', ['flats' => $flats]);
                        }

                        foreach ($flats as $flat) {
                            if ($flat->owner) {
                                $targetUsers->push($flat->owner);
                            }
                            if ($flat->tanent) {
                                $targetUsers->push($flat->tanent);
                            }
                        }
                    }
                }

                // Remove duplicates
                $targetUsers = $targetUsers->unique('id')->filter();

                if ($targetUsers->count() == 0) {
                    Log::info('No target users for classified', ['classified_id' => $classified->id]);
                    continue;
                }

                // Send notifications
                $notificationTitle = '[' . $classified->user->name . '] has shared something';
                $notificationBody = 'New classified: ' . $classified->title;

                $dataPayloadBase = [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'screen' => 'Timeline',
                    'params' => json_encode(['ScreenTab' => 'Classifieds', 'classified_id' => (string)$classified->id, 'building_id' => (string)$classified->building_id]),
                    'categoryId' => 'Classifieds',
                    'channelId' => 'Community',
                    'sound' => 'bellnotificationsound.wav',
                    'type' => 'CLASSIFIED_ADDED',
                    'classified_id' => (string)$classified->id,
                ];
                Log::info('No target users for classified', ['targetUsers' => $targetUsers]);
                foreach ($targetUsers as $user) {
                    try {
                        $dataPayload = $dataPayloadBase;
                        $dataPayload['user_id'] = (string) $user->id;

                        // Send notification using NotificationHelper
                        NotificationHelper::sendNotification(
                            $user->id,
                            $notificationTitle,
                            $notificationBody,
                            $dataPayload,
                            [
                                'from_id' => null,
                                'flat_id' => $user->belongFlatOwner->id ?? $user->belongFlatTanent->id,
                                'building_id' => ($classified->category === 'All Buildings' || $classified->building_id == 0) ? null : $classified->building_id,
                                'type' => 'classified_added',
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send notification to user', [
                            'user_id' => $user->id,
                            'classified_id' => $classified->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Mark as notified so it won't be sent again
                $classified->is_notified = true;
                $classified->save();

                $this->info('Sent notifications for classified id: ' . $classified->id);
            } catch (\Exception $e) {
                Log::error('Error sending classified notification for classified ' . $classified->id . ': ' . $e->getMessage());
            }
        }

        return 0;
    }
}
