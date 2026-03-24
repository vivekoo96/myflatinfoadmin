<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class FCMService
{
    private $projectId;
    private $serviceAccountKey;

    public function __construct()
    {
        $setting = Setting::first();
        $this->projectId = $setting->fcm_project_id ?? config('services.fcm.project_id');
        
        // Handle service account - can be file path or JSON string
        $serviceAccount = $setting->fcm_service_account ?? config('services.fcm.service_account');
        
        // If it's not a full path, assume it's the filename in base_path
        if ($serviceAccount && !file_exists($serviceAccount)) {
            $serviceAccount = base_path($serviceAccount);
        }
        
        $this->serviceAccountKey = $serviceAccount;
        
        \Log::info("FCMService initialized", [
            'project_id' => $this->projectId,
            'service_account_exists' => file_exists($this->serviceAccountKey ?? ''),
            'service_account_path' => $this->serviceAccountKey
        ]);
    }

    /**
     * Send push notification using FCM HTTP v1 API
     */
    public function sendNotification($deviceToken, $title, $body, $data = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                throw new \Exception('Failed to get FCM access token');
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
            
            // Build APNs headers for iOS delivery — these headers help APNs and iOS route & display correctly
            $apnsHeaders = [
                'apns-priority' => '10', // immediate
                'apns-push-type' => 'alert'
            ];

            // Attach alert to APNs payload so iOS devices receive and display notification even when app is in background
            $apnsAps = [
                'alert' => [
                    'title' => $title,
                    'body' => $body
                ],
                'sound' => 'default'
            ];

            $payload = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => $data,
                    'android' => [
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'default'
                        ]
                    ],
                    'apns' => [
                        'headers' => $apnsHeaders,
                        'payload' => [
                            'aps' => $apnsAps
                        ]
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($url, $payload);

            Log::info('FCM v1 Response', [
                'status' => $response->status(),
                'response' => $response->json(),
                'device_token' => substr($deviceToken, 0, 20) . '...'
            ]);

            return [
                'success' => $response->successful(),
                'response' => $response->json(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('FCM v1 Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    /**
     * Get OAuth2 access token for FCM v1 API
     */
    private function getAccessToken()
    {
        try {
            // If service account is a file path
            if (is_string($this->serviceAccountKey) && file_exists($this->serviceAccountKey)) {
                $serviceAccount = json_decode(file_get_contents($this->serviceAccountKey), true);
            } 
            // If service account is JSON string
            else if (is_string($this->serviceAccountKey)) {
                $serviceAccount = json_decode($this->serviceAccountKey, true);
            }
            // If service account is already an array
            else {
                $serviceAccount = $this->serviceAccountKey;
            }

            if (!$serviceAccount || !isset($serviceAccount['private_key'])) {
                throw new \Exception('Invalid service account configuration');
            }

            // Create JWT for Google OAuth2
            $now = time();
            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT'
            ];

            $payload = [
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600
            ];

            $jwt = $this->createJWT($header, $payload, $serviceAccount['private_key']);

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            throw new \Exception('Failed to get access token: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('FCM OAuth Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create JWT token for Google OAuth2
     */
    private function createJWT($header, $payload, $privateKey)
    {
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = '';
        $success = openssl_sign(
            $headerEncoded . '.' . $payloadEncoded,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (!$success) {
            throw new \Exception('Failed to sign JWT');
        }

        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Send to multiple devices
     */
    public function sendToMultipleDevices($deviceTokens, $title, $body, $data = [])
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($deviceTokens as $token) {
            $result = $this->sendNotification($token, $title, $body, $data);
            $results[] = $result;
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'success' => $successCount,
            'failure' => $failureCount,
            'results' => $results
        ];
    }
}
