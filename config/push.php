<?php

return [
    'fcm' => [
        'enabled' => filter_var(env('PUSH_FCM_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'server_key' => env('PUSH_FCM_SERVER_KEY'),
        'endpoint' => env('PUSH_FCM_ENDPOINT', 'https://fcm.googleapis.com/fcm/send'),
    ],

    'onesignal' => [
        'enabled' => filter_var(env('PUSH_ONESIGNAL_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'app_id' => env('PUSH_ONESIGNAL_APP_ID'),
        'api_key' => env('PUSH_ONESIGNAL_API_KEY'),
        'api_url' => env('PUSH_ONESIGNAL_API_URL', 'https://api.onesignal.com/notifications'),
    ],
];
