<?php

return [

    /*
    | Relying Party ID. Leave empty to use the current request host
    | (needed for localhost / ngrok / production domain switches).
    | Set PASSKEYS_RELYING_PARTY_ID only when you must pin a fixed RP ID.
    */
    'relying_party_id' => '',

    'relying_party_id_force' => env('PASSKEYS_RELYING_PARTY_ID'),

    'allowed_origins' => array_values(array_filter(array_map(
        static fn (string $origin): string => rtrim(trim($origin), '/'),
        explode(',', (string) env('PASSKEYS_ALLOWED_ORIGINS', config('app.url'))),
    ))),

    // 管理画面のパスワード後のパスキー。APP_ENV=local のときだけ false で省ける（本番では常に必須）
    'admin_required' => (bool) env('ADMIN_PASSKEY_REQUIRED', true),

    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),

    'timeout' => 60000,

    'guard' => 'admin',

    'middleware' => ['web'],

    'management_middleware' => [],

    'throttle' => 'throttle:6,1',

    'redirect' => '/admin',

];
