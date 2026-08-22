<?php

return [
    'fcm' => [
        'enabled' => filter_var(env('PUSH_FCM_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'project_id' => env('PUSH_FCM_PROJECT_ID', env('GOOGLE_CLOUD_PROJECT')),
        'credentials' => env('PUSH_FCM_CREDENTIALS'),
    ],

    'onesignal' => [
        'enabled' => filter_var(env('PUSH_ONESIGNAL_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'app_id' => env('PUSH_ONESIGNAL_APP_ID'),
        'api_key' => env('PUSH_ONESIGNAL_API_KEY'),
        'api_url' => env('PUSH_ONESIGNAL_API_URL', 'https://api.onesignal.com/notifications'),
    ],

    'kitchen_onesignal' => [
        'enabled' => filter_var(env('PUSH_KITCHEN_ONESIGNAL_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'app_id' => env('PUSH_KITCHEN_ONESIGNAL_APP_ID'),
        'api_key' => env('PUSH_KITCHEN_ONESIGNAL_API_KEY'),
        'api_url' => env('PUSH_KITCHEN_ONESIGNAL_API_URL', 'https://api.onesignal.com/notifications'),
    ],
];
