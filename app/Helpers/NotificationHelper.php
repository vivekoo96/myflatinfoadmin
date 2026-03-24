<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\User;
use App\Models\Flat;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    /**
     * Send notification to a user with flat context
     *
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param string $type
     * @param array $data
     * @param int|null $fromUserId
     * @param int|null $flatId
     * @param bool $sendPush
     * @return Notification|null
     */
    public static function sendNotification(
        $userId, 
        $title, 
        $body, 
        $type = 'general', 
        $data = [], 
        $fromUserId = null, 
        $flatId = null, 
        $sendPush = true
    ) {
        try {
            // Get user
            $user = User::find($userId);
            if (!$user) {
                Log::error("NotificationHelper: User not found with ID: {$userId}");
                return null;
            }

            // Get flat and building info
            $buildingId = null;
            if ($flatId) {
                $flat = Flat::find($flatId);
                $buildingId = $flat ? $flat->building_id : null;
            } else {
                // Try to get user's flat
                $flat = Flat::where('owner_id', $userId)
                           ->orWhere('tanent_id', $userId)
                           ->first();
                if ($flat) {
                    $flatId = $flat->id;
                    $buildingId = $flat->building_id;
                }
            }

            // Create database notification
            $notification = Notification::create([
                'user_id' => $userId,
                'from_id' => $fromUserId,
                'flat_id' => $flatId,
                'building_id' => $buildingId,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'dataPayload' => $data,
                'admin_read' => 0
            ]);

            // Send push notification if requested and user has FCM token
            if ($sendPush && $user->fcm_token) {
                self::sendPushNotification($user->fcm_token, $title, $body, $data, $type);
            }

            return $notification;

        } catch (\Exception $e) {
            Log::error("NotificationHelper: Error sending notification: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send notification to multiple users
     *
     * @param array $userIds
     * @param string $title
     * @param string $body
     * @param string $type
     * @param array $data
     * @param int|null $fromUserId
     * @param int|null $flatId
     * @param bool $sendPush
     * @return array
     */
    public static function sendNotificationToMultiple(
        $userIds, 
        $title, 
        $body, 
        $type = 'general', 
        $data = [], 
        $fromUserId = null, 
        $flatId = null, 
        $sendPush = true
    ) {
        $notifications = [];
        foreach ($userIds as $userId) {
            $notification = self::sendNotification($userId, $title, $body, $type, $data, $fromUserId, $flatId, $sendPush);
            if ($notification) {
                $notifications[] = $notification;
            }
        }
        return $notifications;
    }

    /**
     * Send notification to all users in a building
     *
     * @param int $buildingId
     * @param string $title
     * @param string $body
     * @param string $type
     * @param array $data
     * @param int|null $fromUserId
     * @param bool $sendPush
     * @return array
     */
    public static function sendNotificationToBuilding(
        $buildingId, 
        $title, 
        $body, 
        $type = 'general', 
        $data = [], 
        $fromUserId = null, 
        $sendPush = true
    ) {
        // Get all users in the building (owners and tenants)
        $flats = Flat::where('building_id', $buildingId)->get();
        $userIds = [];
        
        foreach ($flats as $flat) {
            if ($flat->owner_id) {
                $userIds[] = $flat->owner_id;
            }
            if ($flat->tanent_id) {
                $userIds[] = $flat->tanent_id;
            }
        }
        
        $userIds = array_unique($userIds);
        return self::sendNotificationToMultiple($userIds, $title, $body, $type, $data, $fromUserId, null, $sendPush);
    }

    /**
     * Send notification to all users in a flat (owner and tenant)
     *
     * @param int $flatId
     * @param string $title
     * @param string $body
     * @param string $type
     * @param array $data
     * @param int|null $fromUserId
     * @param bool $sendPush
     * @return array
     */
    public static function sendNotificationToFlat(
        $flatId, 
        $title, 
        $body, 
        $type = 'general', 
        $data = [], 
        $fromUserId = null, 
        $sendPush = true
    ) {
        $flat = Flat::find($flatId);
        if (!$flat) {
            Log::error("NotificationHelper: Flat not found with ID: {$flatId}");
            return [];
        }

        $userIds = [];
        if ($flat->owner_id) {
            $userIds[] = $flat->owner_id;
        }
        if ($flat->tanent_id) {
            $userIds[] = $flat->tanent_id;
        }

        return self::sendNotificationToMultiple($userIds, $title, $body, $type, $data, $fromUserId, $flatId, $sendPush);
    }

    /**
     * Send push notification via FCM
     *
     * @param string $fcmToken
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string $type
     * @return bool
     */
    private static function sendPushNotification($fcmToken, $title, $body, $data = [], $type = 'general')
    {
        try {
            $setting = Setting::first();
            $serverKey = $setting->fcm_key ?? null;
            
            if (!$serverKey) {
                Log::error("NotificationHelper: FCM server key not configured");
                return false;
            }

            $payload = [
                "to" => $fcmToken,
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                    "sound" => "default",
                ],
                "data" => array_merge([
                    "type" => $type,
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK"
                ], $data),
                "priority" => "high"
            ];

            $headers = [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                Log::info("NotificationHelper: Push notification sent successfully to token: " . substr($fcmToken, 0, 20) . "...");
                return true;
            } else {
                Log::error("NotificationHelper: Push notification failed with HTTP code: {$httpCode}, Response: {$response}");
                return false;
            }

        } catch (\Exception $e) {
            Log::error("NotificationHelper: Error sending push notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public static function markAsRead($notificationId, $userId)
    {
        try {
            $notification = Notification::where('id', $notificationId)
                                      ->where('user_id', $userId)
                                      ->first();
            
            if ($notification) {
                $notification->markAsRead();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("NotificationHelper: Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notifications count for user
     *
     * @param int $userId
     * @return int
     */
    public static function getUnreadCount($userId)
    {
        return Notification::where('user_id', $userId)
                          ->whereNull('read_at')
                          ->count();
    }
}
