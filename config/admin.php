<?php

return [
    'demo' => [
        'system_login_id' => env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys'),
        'store_login_id' => env('DEMO_STORE_LOGIN_ID', 'demo_store'),
        'store_password' => env('DEMO_STORE_PASSWORD', 'DemoStore#2026'),
        'project_login_id' => env('DEMO_PROJECT_LOGIN_ID', 'demo_proj'),
        'project_password' => env('DEMO_PROJECT_PASSWORD', 'DemoProj#2026'),
        'staff_login_id' => env('DEMO_STAFF_LOGIN_ID', 'demo_staff'),
        'staff_password' => env('DEMO_STAFF_PASSWORD', 'DemoStaff#2026'),
    ],
    'password' => env('ADMIN_PASSWORD', 'reserve'),
];
