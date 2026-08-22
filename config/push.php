<?php

return [
    'fcm' => [
        'enabled' => filter_var(env('PUSH_FCM_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'project_id' => env('PUSH_FCM_PROJECT_ID', env('GOOGLE_CLOUD_PROJECT')),
        'credentials' => env('PUSH_FCM_CREDENTIALS'),
    ],
];
