<?php

return [
    'enabled' => filter_var(env('WALLET_PASS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'team_id' => env('WALLET_PASS_TEAM_ID', ''),
    'type_id' => env('WALLET_PASS_TYPE_ID', ''),
    'cert_path' => env('WALLET_PASS_CERT_PATH', storage_path('app/wallet_pass/certificates/pass.cer')),
    'key_path' => env('WALLET_PASS_KEY_PATH', storage_path('app/wallet_pass/certificates/iOS-Passkey-Key.key')),
    'wwdr_path' => env('WALLET_PASS_WWDR_PATH', storage_path('app/wallet_pass/certificates/AppleWWDRCAG4.cer')),
    'assets_path' => env('WALLET_PASS_ASSETS_PATH', storage_path('app/wallet_pass/assets')),
    'key_passphrase' => env('WALLET_PASS_KEY_PASSPHRASE', ''),
    // PassKit Web Service のベース URL（末尾スラッシュなし）。未設定時は APP_URL から自動導出。
    'web_service_url' => env(
        'APPLE_WALLET_WEBSERVICE_URL',
        ($appUrl = rtrim((string) env('APP_URL', ''), '/')) !== '' ? $appUrl.'/api/apple-wallet' : ''
    ),
    'auth_token_secret' => env('APPLE_WALLET_AUTH_TOKEN_SECRET', ''),
    'apns_key_id' => env('APPLE_WALLET_APNS_KEY_ID', ''),
    'apns_team_id' => env('APPLE_WALLET_TEAM_ID', ''),
    'apns_auth_key_path' => env('APPLE_WALLET_APNS_AUTH_KEY_PATH', ''),
    'apns_use_sandbox' => filter_var(env('APPLE_WALLET_APNS_USE_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
    'allow_demo_user' => filter_var(env('WALLET_ADD_DEMO_USER', false), FILTER_VALIDATE_BOOLEAN),
];
