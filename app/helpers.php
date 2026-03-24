<?php

use App\Helpers\NotificationHelper;

if (!function_exists('sendNotification')) {
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
     * @return \App\Models\Notification|null
     */
    function sendNotification($userId, $title, $body, $type = 'general', $data = [], $fromUserId = null, $flatId = null, $sendPush = true)
    {
        return NotificationHelper::sendNotification($userId, $title, $body, $type, $data, $fromUserId, $flatId, $sendPush);
    }
}

if (!function_exists('sendNotificationToFlat')) {
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
    function sendNotificationToFlat($flatId, $title, $body, $type = 'general', $data = [], $fromUserId = null, $sendPush = true)
    {
        return NotificationHelper::sendNotificationToFlat($flatId, $title, $body, $type, $data, $fromUserId, $sendPush);
    }
}

if (!function_exists('sendNotificationToBuilding')) {
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
    function sendNotificationToBuilding($buildingId, $title, $body, $type = 'general', $data = [], $fromUserId = null, $sendPush = true)
    {
        return NotificationHelper::sendNotificationToBuilding($buildingId, $title, $body, $type, $data, $fromUserId, $sendPush);
    }
}

if (!function_exists('sendNotificationToMultiple')) {
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
    function sendNotificationToMultiple($userIds, $title, $body, $type = 'general', $data = [], $fromUserId = null, $flatId = null, $sendPush = true)
    {
        return NotificationHelper::sendNotificationToMultiple($userIds, $title, $body, $type, $data, $fromUserId, $flatId, $sendPush);
    }
}

if (!function_exists('markNotificationAsRead')) {
    /**
     * Mark notification as read
     * 
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    function markNotificationAsRead($notificationId, $userId)
    {
        return NotificationHelper::markAsRead($notificationId, $userId);
    }
}

if (!function_exists('getUnreadNotificationCount')) {
    /**
     * Get unread notifications count for user
     * 
     * @param int $userId
     * @return int
     */
    function getUnreadNotificationCount($userId)
    {
        return NotificationHelper::getUnreadCount($userId);
    }
}

if (!function_exists('sendEmergencyNotification')) {
    /**
     * Send emergency notification to building (high priority)
     * 
     * @param int $buildingId
     * @param string $title
     * @param string $message
     * @param int|null $fromUserId
     * @return array
     */
    function sendEmergencyNotification($buildingId, $title, $message, $fromUserId = null)
    {
        return NotificationHelper::sendNotificationToBuilding(
            $buildingId, 
            'EMERGENCY: ' . $title, 
            $message, 
            'emergency', 
            ['priority' => 'high', 'emergency' => true], 
            $fromUserId, 
            true
        );
    }
}
