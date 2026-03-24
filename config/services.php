<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    
// 'apns' => [
//     'key_id' => env('APNS_KEY_ID'),
//     'team_id' => env('APNS_TEAM_ID'),
//     'bundle_id' => env('APNS_BUNDLE_ID'),
//     'private_key_path' => storage_path('app/apns/MyFLATINFO.p8'),
//     'production' => env('APNS_PRODUCTION', false),
// ],

'apns' => [
    'user' => [
        'key_id' => env('APNS_KEY_ID_USER'),
        'team_id' => env('APNS_TEAM_ID_USER'),
        'bundle_id' => env('APNS_BUNDLE_ID_USER'),
        'private_key_path' => storage_path('app/apns/MyFLATINFO.p8'),
        'production' => env('APNS_MYFLAT_PRODUCTION', false),
    ],

    'issue' => [
        'key_id' => env('APNS_KEY_ID_ISSUE'),
        'team_id' => env('APNS_TEAM_ID_ISSUE'),
        'bundle_id' => env('APNS_BUNDLE_ID_ISSUE'),
        'private_key_path' => storage_path('app/apns/MyFLATINFO.p8'),
        'production' => env('APNS_ISSUE_PRODUCTION', false),
    ],
],



    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    
    'fcm' => [
    'project_id' => env('FCM_PROJECT_ID'),
    'service_account' => env('FCM_SERVICE_ACCOUNT'),
],

];
