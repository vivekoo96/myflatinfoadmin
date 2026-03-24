<?php

namespace App\Helpers;

use App\Models\Notification as DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Pushok\AuthProvider\Token;
use Pushok\Client as ApnsClient;
use Pushok\Notification as ApnsNotification;
use Pushok\Payload;
use Pushok\Payload\Alert;

class NotificationHelper2
{
    /**
     * Send notification to user across all devices and save to database
     *
     * @param int $userId - The user to send notification to
     * @param string $title - Notification title
     * @param string $body - Notification body
     * @param array $dataPayload - Additional data payload
     * @param array $options - Optional parameters
     *   - from_id: Sender user ID (default: null)
     *   - flat_id: Flat ID (default: null)
     *   - building_id: Building ID (default: null)
     *   - type: Notification type (default: 'general')
     *   - save_to_db: Whether to save notification to database (default: true)
     *   - apns_client: Pre-configured APNs client (optional)
     *   - ios_sound: Custom iOS sound (default: 'bellnotificationsound.wav')
     * 
     * @return array - Result with success status and message
     */
public static function sendNotification(
    int $userId,
    string $title,
    string $body,
    array $dataPayload = [],
    array $options = [],
  $app_name = null 
): array {

    // \Log::info("Firebase notification init", $app_name);

    // Extract options
    $fromId        = $options['from_id'] ?? null;
    $flatId        = $options['flat_id'] ?? null;
    $roleId        = $options['role_id'] ?? null;
    $buildingId    = $options['building_id'] ?? null;
    $type          = $options['type'] ?? 'general';
    $saveToDB      = $options['save_to_db'] ?? true;
    $apnsClient    = $options['apns_client'] ?? null;
    $iosSound      = $options['ios_sound'] ?? 'bellnotificationsound.wav';

    try {

        // Save DB Notification
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

            // Inject notification_id into JSON params (if exists)
            if (isset($dataPayload['params'])) {
                $params = json_decode($dataPayload['params'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $params['notification_id'] = $notification->id;
                    $dataPayload['params'] = json_encode($params);
                }
            }
        }

        // Fetch devices
$devices = DB::table('user_devices')
    ->when($app_name, function ($query, $app_name) {
        if (is_array($app_name)) {
            return $query->whereIn('app_name', $app_name);
        }
        return $query->where('app_name', $app_name);
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

        // Initialize Firebase
        $firebaseFactory   = (new Factory)->withServiceAccount(base_path('myflatinfo-firebase-adminsdk.json'));
        $firebaseMessaging = $firebaseFactory->createMessaging();

        // Init APNs if needed
        if (!$apnsClient && self::hasIOSDevices($devices)) {
            $apnsClient = self::initializeApnsClient();
        }

        $successCount = $failureCount = 0;

        // Send to each device
        foreach ($devices as $device) {
            $token = $device->fcm_token;
            $type  = strtolower($device->device_type);

            if (in_array($type, ['android', 'web'])) {

                $result = self::sendFirebaseNotification(
                    $firebaseMessaging,
                    $token,
                    $title,
                    $body,
                    $dataPayload
                );

            } elseif ($type === 'ios' && $apnsClient) {
     
                $result = self::sendApnsNotification(
                    $apnsClient,
                    $token,
                    $title,
                    $body,
                    $dataPayload,
                    $iosSound  // ✅ correct parameter
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
            'jhvcsjh'=>$devices
        ];

    } catch (\Exception $e) {

        Log::error('NotificationHelper error: ' . $e->getMessage(), [
            'user_id' => $userId,
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'message' => 'Failed to send notification: ' . $e->getMessage(),
            'devices_notified' => 0
        ];
    }
}


    /**
     * Send Firebase notification (Android/Web)
     */
    private static function sendFirebaseNotification(
        $firebaseMessaging,
        string $token,
        string $title,
        string $body,
        array $dataPayload
    ): bool {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($dataPayload);

            $firebaseMessaging->send($message);
            return true;

        } catch (\Exception $e) {
            Log::error("Firebase notification error for token $token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send APNs notification (iOS)
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
            $alert = Alert::create()
                ->setTitle($title)
                ->setBody($body);

            $payload = Payload::create()
                ->setAlert($alert)
                ->setSound($sound);

            // Add custom data to payload
            foreach ($dataPayload as $key => $value) {
                $payload->setCustomValue($key, $value);
            }

            $notification = new ApnsNotification($payload, $token);
            $apnsClient->addNotification($notification);
            $responses = $apnsClient->push();

            foreach ($responses as $response) {
                if ($response->getStatusCode() !== 200) {
                    Log::error('APNs Error', [
                        'status' => $response->getStatusCode(),
                        'reason' => $response->getReasonPhrase(),
                        'error' => $response->getErrorReason()
                    ]);
                    return false;
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error("APNs notification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if there are any iOS devices in the collection
     */
    private static function hasIOSDevices($devices): bool
    {
        foreach ($devices as $device) {
            if (strtolower($device->device_type) === 'ios') {
                return true;
            }
        }
        return false;
    }

    /**
     * Initialize APNs client
     * Note: You'll need to configure this based on your APNs setup
     */
    private static function initializeApnsClient(): ?ApnsClient
    {
        try {
            // Configure based on your APNs setup
            // This is a placeholder - adjust according to your configuration
            $authProvider = Token::create([
                'key_id' => config('services.apns.key_id'),
                'team_id' => config('services.apns.team_id'),
                'app_bundle_id' => config('services.apns.bundle_id'),
                'private_key_path' => config('services.apns.private_key_path'),
            ]);

            return new ApnsClient($authProvider, $production = false);

        } catch (\Exception $e) {
            Log::error("Failed to initialize APNs client: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send bulk notifications to multiple users
     *
     * @param array $userIds - Array of user IDs
     * @param string $title
     * @param string $body
     * @param array $dataPayload
     * @param array $options
     * @return array - Summary of results
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
            'total_devices' => 0
        ];

        foreach ($userIds as $userId) {
            $result = self::sendNotification($userId, $title, $body, $dataPayload, $options,$app_name);
            
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