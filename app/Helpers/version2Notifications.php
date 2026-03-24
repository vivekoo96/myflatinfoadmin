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
     * Send notification to user across all devices and save to database
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
        $apnsClient = $options['apns_client'] ?? null;

        try {

            /** -------------------------------
             * Save Notification In Database
             * ------------------------------- */
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

            /** -------------------------------
             * Fetch User Devices
             * ------------------------------- */
            $devices = DB::table('user_devices')
                ->when($app_name, function ($query) use ($app_name) {
                    return is_array($app_name)
                        ? $query->whereIn('app_name', $app_name)
                        : $query->where('app_name', $app_name);
                })
                ->where('user_id', $userId)
                ->whereNotNull('fcm_token')
                ->where('is_active', 1)
                ->select('fcm_token', 'device_type')
                ->get();

            if ($devices->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'Notification saved but no active devices found',
                    'devices_notified' => 0
                ];
            }

            /** -------------------------------
             * Initialize Firebase Messaging
             * ------------------------------- */
            $firebase = (new Factory)->withServiceAccount(
                base_path('myflatinfo-firebase-adminsdk.json')
            )->createMessaging();

            /** -------------------------------
             * Initialize APNs (if needed)
             * ------------------------------- */
            if (!$apnsClient && self::hasIOSDevices($devices)) {
                $apnsClient = self::initializeApnsClient();
            }

            $successCount = 0;
            $failureCount = 0;

            /** -------------------------------
             * Send to all devices
             * ------------------------------- */
            foreach ($devices as $device) {

                $token = $device->fcm_token;
                $deviceType = strtolower($device->device_type);
                $result = false;

                if (in_array($deviceType, ['android', 'web'])) {
                    $result = self::sendFirebaseNotification(
                        $firebase, $token, $title, $body, $dataPayload
                    );
                }

                if ($deviceType === 'ios' && $apnsClient) {
                    $result = self::sendApnsNotification(
                        $apnsClient, $token, $title, $body, $dataPayload, $iosSound
                    );
                }

                $result ? $successCount++ : $failureCount++;
            }

            return [
                'success' => true,
                'message' => 'Notification sent successfully',
                'devices_notified' => $successCount,
                'failures' => $failureCount,
                'total_devices' => $devices->count(),
            ];

        } catch (\Exception $e) {
            Log::error('NotificationHelper error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
                'devices_notified' => 0
            ];
        }
    }

    /**
     * Firebase (Android/Web)
     */
    private static function sendFirebaseNotification(
        $firebaseMessaging,
        string $token,
        string $title,
        string $body,
        array $dataPayload
    ): bool {
        try {
              // Insert required Android notification settings
        $androidConfig = [
            'priority' => 'high',
            'ttl' => '3600s',
            'notification' => [
                'channel_id' => $dataPayload['channelId'] ?? 'default',
                'sound'      => $dataPayload['sound'] ?? 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'visibility' => 'public',
                'notification_priority' => 'PRIORITY_HIGH',
            ],
        ];

        // KEEP dataPayload because Flutter apps often read from it
        $dataPayload = array_merge($dataPayload, [
            'categoryId' => $dataPayload['categoryId'] ?? '',
            'channelId'  => $dataPayload['channelId'] ?? 'default',
            'sound'      => $dataPayload['sound'] ?? 'default',
        ]);
        
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FirebaseNotification::create($title, $body))
            ->withAndroidConfig($androidConfig)
            ->withData($dataPayload);

            $firebaseMessaging->send($message);
            return true;

        } catch (\Exception $e) {
            Log::error("Firebase Error: {$e->getMessage()} (token: $token)");
            return false;
        }
    }

    /**
     * APNs (iOS)
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

            foreach ($dataPayload as $key => $value) {
                $payload->setCustomValue($key, $value);
            }

            $notification = new ApnsNotification($payload, $token);
            $apnsClient->addNotification($notification);

            $responses = $apnsClient->push();

            foreach ($responses as $response) {
                if ($response->getStatusCode() !== 200) {
                    Log::error("APNs error", [
                        'token' => $token,
                        'status' => $response->getStatusCode(),
                        'reason' => $response->getReasonPhrase(),
                        'error' => $response->getErrorReason(),
                    ]);
                    return false;
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error("APNs exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if any device is iOS
     */
    private static function hasIOSDevices($devices): bool
    {
        return $devices->contains(fn ($d) => strtolower($d->device_type) === 'ios');
    }

    /**
     * Initialize APNs Client
     */
    private static function initializeApnsClient(): ?ApnsClient
    {
        try {
            $authProvider = ApnsToken::create([
                'key_id' => config('services.apns.key_id'),
                'team_id' => config('services.apns.team_id'),
                'app_bundle_id' => config('services.apns.bundle_id'),
                'private_key_path' => config('services.apns.private_key_path'),
                'private_key_secret' => null,
            ]);

            $production = config('services.apns.production', false);

            return new ApnsClient($authProvider, $production);

        } catch (\Exception $e) {
            Log::error("Failed to initialize APNs client: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Bulk Notifications
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
            'successful' => 0,
            'failed' => 0,
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
