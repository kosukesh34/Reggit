<?php

return [
    'fcm' => [
        'enabled' => filter_var(env('PUSH_FCM_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'server_key' => env('PUSH_FCM_SERVER_KEY'),
        'endpoint' => env('PUSH_FCM_ENDPOINT', 'https://fcm.googleapis.com/fcm/send'),
    ],
];

