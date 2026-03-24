<?php

namespace App\Helpers;

use App\Models\Notification as DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Pushok\AuthProvider\Token as ApnsToken;
use Pushok\Client as ApnsClient;
use Pushok\Notification as ApnsNotification;
use Pushok\Payload;
use Pushok\Payload\Alert;



class NotificationHelper2
{
    /**
     * Store created APNs clients for reuse
     */
    private static array $apnsClients = [];

    /**
     * APP NAME MAPPING (FIX)
     * You always send ['issue'], so map it to real app name
     */
    private static array $appNameMap = [
        'issue' => 'issue',  // update this if real DB name differs
        'user'  => 'user'
    ];

    /**
     * Send notification
     */
    public static function sendNotification(
        int $userId,
        string $title,
        string $body,
        array $dataPayload = [],
        array $options = [],
        $app_name = null
    ): array {

        $fromId     = $options['from_id'] ?? null;
        $flatId     = $options['flat_id'] ?? null;
        $roleId     = $options['role_id'] ?? null;
        $buildingId = $options['building_id'] ?? null;
        $type       = $options['type'] ?? 'general';
        $saveToDB   = $options['save_to_db'] ?? true;
        $iosSound   = $options['ios_sound'] ?? 'bellnotificationsound.wav';

        try {

            /** Save notification in DB */
            if ($saveToDB) {
                $notification = new DatabaseNotification();
                $notification->user_id     = $userId;
                $notification->from_id     = $fromId;
                $notification->flat_id     = $flatId;
                $notification->role_id     = $roleId;
                $notification->building_id = $buildingId;
                $notification->title       = $title;
                $notification->body        = $body;
                $notification->type        = $type;
                $notification->dataPayload = $dataPayload;
                $notification->status      = 0;
                $notification->save();

                if (isset($dataPayload['params'])) {
                    $params = json_decode($dataPayload['params'], true);
                    if (is_array($params)) {
                        $params['notification_id'] = $notification->id;
                        $dataPayload['params'] = json_encode($params);
                    }
                }
            }

            /**
             * FIX — normalize app_name
             * You always send ['issue'], so map it
             */
            if ($app_name) {
                $app_name = array_map(function ($a) {
                    return NotificationHelper2::$appNameMap[$a] ?? $a;
                }, (array)$app_name);
            }

            /** Fetch devices */
            $devices = DB::table('user_devices')
                ->when($app_name, function ($q) use ($app_name) {
                    return $q->whereIn('app_name', $app_name);
                })
                ->where('user_id', $userId)
                // Include Android/Web devices (which will have fcm_token) and iOS devices (may have fcm_token or APNs-only tokens)
                ->where(function ($q) {
                    $q->whereNotNull('fcm_token')
                      ->orWhere('device_type', 'ios');
                })
                ->where('is_active', 1)
                ->select('fcm_token', 'device_type', 'app_name')
                ->get();

            Log::info('Devices Found', [
                'app_filter' => $app_name,
                'devices' => $devices->toArray()
            ]);

            if ($devices->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'Notification saved, no devices found.',
                    'devices_notified' => 0
                ];
            }

            /** Firebase init */
            $firebase = (new Factory)
                ->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'))
                ->createMessaging();

            $successCount = 0;
            $failureCount = 0;

            foreach ($devices as $device) {
                $token      = $device->fcm_token;
                $appName    = $device->app_name;
                $deviceType = strtolower($device->device_type);
                $result     = false;

                /** Android/Web -> Firebase */
                if (in_array($deviceType, ['android', 'web'])) {
                    $result = self::sendFirebaseNotification(
                        $firebase, $token, $title, $body, $dataPayload
                    );
                }
                
                
                
                

                /** iOS -> APNs */
    //             if ($deviceType === 'ios') {

    //                 $apnsClient = self::getApnsClientForApp($appName);
                    
    //                     if (!$token || trim($token) === "") {
    //     Log::warning("Skipping iOS device -> Missing APNs token", [
    //         'user_id' => $userId,
    //         'device'  => $device
    //     ]);
    //     continue;
    // }

    //                 Log::info('APNs Client Loaded', [
    //                     'appName' => $appName,
    //                     'client_loaded' => $apnsClient ? true : false
    //                 ]);

    //                 if ($apnsClient) {
    //                     $result = self::sendApnsNotification(
    //                         $apnsClient,
    //                         $token,
    //                         $title,
    //                         $body,
    //                         $dataPayload,
    //                         $iosSound
    //                     );
    //                 }
    //             }
                
                
                
                if ($deviceType === 'ios') {

    if (!$token || trim($token) === "") {
        Log::warning("Skipping iOS device -> Missing APNs token", [
            'user_id' => $userId,
            'device'  => $device
        ]);
        continue;
    }

    $apnsClient = self::getApnsClientForApp($appName);

    if ($apnsClient) {
        $result = self::sendApnsNotification(
            $apnsClient,
            $token,
            $title,
            $body,
            $dataPayload,
            $iosSound
        );
    }
}


                $result ? $successCount++ : $failureCount++;
            }

            return [
                'success' => true,
                'message' => 'Notification sent.',
                'devices_notified' => $successCount,
                'failures' => $failureCount,
                'total_devices' => $devices->count(),
            ];

        } catch (\Exception $e) {

            Log::error('NotificationHelper Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'message' => 'Failed: ' . $e->getMessage(),
                'devices_notified' => 0
            ];
        }
    }

    /**
     * Firebase notify
     */
   private static function sendFirebaseNotification(
        $firebaseMessaging,
        string $token,
        string $title,
        string $body,
        array $dataPayload
    ): bool {
        try {
            // Extract sound from dataPayload or use default
            $sound = !empty($dataPayload['sound']) ? $dataPayload['sound'] : 'default';
            $channelId = !empty($dataPayload['channelId']) ? $dataPayload['channelId'] : 'default';

        
            // Android config: properly set notification to show when app is killed
            $androidConfig = [
                'priority' => 'high',
                'ttl' => '3600s',
                'notification' => [
                    'sound' => $sound,
                    'channel_id' => $channelId,
                    ]
            ];
            
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withAndroidConfig($androidConfig)
                ->withData($dataPayload);

            $firebaseMessaging->send($message);
            return true;
            

        } catch (\Exception $e) {
            Log::error("Firebase Error: {$e->getMessage()} token:$token");
            return false;
        }
    }


    /**
     * APNs
     */
    private static function sendApnsNotification(
        ApnsClient $apnsClient,
        string $token,
        string $title,
        string $body,
        array $dataPayload,
        string $sound
    ): bool {
        try {
            $alert = Alert::create()->setTitle($title)->setBody($body);

            $payload = Payload::create()
                ->setAlert($alert)
                ->setSound($sound);

            foreach ($dataPayload as $k => $v) {
                $payload->setCustomValue($k, $v);
            }

            $notification = new ApnsNotification($payload, $token);
            $apnsClient->addNotification($notification);

            $responses = $apnsClient->push();

            foreach ($responses as $response) {
                if ($response->getStatusCode() !== 200) {
                    Log::error("APNs Error", [
                        'token'  => $token,
                        'status' => $response->getStatusCode(),
                        'reason' => $response->getReasonPhrase(),
                        'error'  => $response->getErrorReason(),
                    ]);
                    return false;
                }
            }
            return true;

        } catch (\Exception $e) {
            Log::error("APNs Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Initialize APNs client
     */
    private static function getApnsClientForApp(string $appName): ?ApnsClient
    {
        if (isset(self::$apnsClients[$appName])) {
            return self::$apnsClients[$appName];
        }

        $config = config("services.apns.$appName");

        if (!$config) {
            Log::error("APNs config missing for app: $appName");
            return null;
        }

        try {
            $authProvider = ApnsToken::create([
                'key_id'          => $config['key_id'],
                'team_id'         => $config['team_id'],
                'app_bundle_id'   => $config['bundle_id'],
                'private_key_path'=> $config['private_key_path'],
            ]);

            $client = new ApnsClient($authProvider, $config['production']);

            self::$apnsClients[$appName] = $client;
            return $client;

        } catch (\Exception $e) {
            Log::error("Failed to init APNs client for $appName: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Bulk notifications
     */
    public static function sendBulkNotifications(
        array $userIds,
        string $title,
        string $body,
        array $dataPayload = [],
        array $options = [],
        $app_name = null
    ): array {
        $results = [
            'total_users' => count($userIds),
            'successful'  => 0,
            'failed'      => 0,
            'total_devices' => 0,
        ];

        foreach ($userIds as $userId) {
            $result = self::sendNotification(
                $userId, $title, $body, $dataPayload, $options, $app_name
            );

            if ($result['success']) {
                $results['successful']++;
                $results['total_devices'] += $result['devices_notified'];
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }
}
