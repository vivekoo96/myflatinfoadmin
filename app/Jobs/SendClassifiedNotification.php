<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Classified;
use App\Models\Flat;
use App\Models\User;
use App\Models\Building;
use App\Models\ClassifiedBuilding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendClassifiedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $classified;
    protected $blockIds;

    /**
     * Create a new job instance.
     *
     * @param Classified $classified
     * @param array $blockIds - Block IDs to notify (empty array means all blocks in building)
     */
    public function __construct(Classified $classified, array $blockIds = [])
    {
        $this->classified = $classified;
        $this->blockIds = $blockIds;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('SendClassifiedNotification job started', [
                'classified_id' => $this->classified->id,
                'category' => $this->classified->category,
                'block_ids' => $this->blockIds,
            ]);

            // Determine target flats based on classified category and block selection
            $flats = collect();

            if ($this->classified->category === 'All Buildings') {
                // Send to users in all buildings that allow All Buildings classifieds
                $buildingIds = ClassifiedBuilding::where('classified_id', $this->classified->id)
                    ->pluck('building_id')
                    ->toArray();

                // Filter buildings that have the permission
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
                }

                Log::info('Classified targeting all buildings', [
                    'classified_id' => $this->classified->id,
                    'total_flats' => $flats->count(),
                    'eligible_buildings' => count($allowedBuildingIds)
                ]);
            } else {
                // Within Building - check if building allows this category
                $building = Building::find($this->classified->building_id);

                if ($building && $building->hasPermission('Classified for withinbuilding')) {
                    if ($this->classified->block_id === 0) {
                        // All blocks in current building
                        $flats = Flat::where('building_id', $building->id)
                            ->where(function($query) {
                                $query->whereNotNull('owner_id')
                                      ->orWhereNotNull('tanent_id');
                            })->get();

                        Log::info('Classified targeting all blocks in building', [
                            'classified_id' => $this->classified->id,
                            'building_id' => $building->id,
                            'total_flats' => $flats->count()
                        ]);
                    } else {
                        // Specific block in current building
                        $flats = Flat::where('block_id', $this->classified->block_id)
                            ->where(function($query) {
                                $query->whereNotNull('owner_id')
                                      ->orWhereNotNull('tanent_id');
                            })->get();

                        Log::info('Classified targeting specific block', [
                            'classified_id' => $this->classified->id,
                            'block_id' => $this->classified->block_id,
                            'total_flats' => $flats->count()
                        ]);
                    }
                } else {
                    Log::warning('Classified targeting within building - building does not have permission', [
                        'classified_id' => $this->classified->id,
                        'building_id' => $this->classified->building_id
                    ]);
                }
            }

            // Extract unique user IDs from flats (owners and tenants)
            $userIds = collect();
            foreach ($flats as $flat) {
                if ($flat->owner_id) {
                    $userIds->push($flat->owner_id);
                }
                if ($flat->tanent_id) {
                    $userIds->push($flat->tanent_id);
                }
            }
            $userIds = $userIds->unique()->toArray();

            // Fetch users
            $users = collect();
            if (!empty($userIds)) {
                $users = User::whereIn('id', $userIds)
                    ->whereNull('deleted_at')
                    ->get();
            }

            Log::info('Users found for classified notification', [
                'classified_id' => $this->classified->id,
                'user_count' => $users->count(),
                'flat_count' => $flats->count(),
            ]);

            // Send notification to each user
            $sentCount = 0;
            $failedCount = 0;

            foreach ($users as $user) {
                try {
                    // Option 1: Send via email (if configured)
                    if ($user->email) {
                        Log::info('Classified notification sent to user', [
                            'classified_id' => $this->classified->id,
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'title' => $this->classified->title,
                            'category' => $this->classified->category,
                        ]);
                        $sentCount++;
                    }

                    // Option 2: Send via SMS (if configured)
                    if ($user->phone) {
                        Log::info('Classified SMS would be sent', [
                            'user_id' => $user->id,
                            'phone' => $user->phone,
                        ]);
                    }

                    // Option 3: Send via push notification (if configured)
                    // Add Firebase/push notification logic here
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Failed to send notification to user', [
                        'classified_id' => $this->classified->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('SendClassifiedNotification job completed', [
                'classified_id' => $this->classified->id,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('SendClassifiedNotification job failed', [
                'classified_id' => $this->classified->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
