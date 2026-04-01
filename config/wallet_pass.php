<?php

return [
    'enabled' => filter_var(env('WALLET_PASS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'team_id' => env('WALLET_PASS_TEAM_ID', ''),
    'type_id' => env('WALLET_PASS_TYPE_ID', ''),
    'cert_path' => env('WALLET_PASS_CERT_PATH', storage_path('app/wallet_pass/certificates/pass.cer')),
    'key_path' => env('WALLET_PASS_KEY_PATH', storage_path('app/wallet_pass/certificates/iOS-Passkey-Key.key')),
    'wwdr_path' => env('WALLET_PASS_WWDR_PATH', storage_path('app/wallet_pass/certificates/AppleWWDRCAG3.cer')),
    'assets_path' => env('WALLET_PASS_ASSETS_PATH', storage_path('app/wallet_pass/assets')),
    'key_passphrase' => env('WALLET_PASS_KEY_PASSPHRASE', ''),
    'web_service_url' => env('WALLET_PASS_WEB_SERVICE_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/api/wallet/v1/{project_key}'),
    'auth_token_length' => (int) env('WALLET_PASS_AUTH_TOKEN_LENGTH', 32),
    'apple_push_enabled' => filter_var(env('APPLE_PUSH_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'apple_push_team_id' => env('APPLE_PUSH_TEAM_ID', ''),
    'apple_push_key_id' => env('APPLE_PUSH_KEY_ID', ''),
    'apple_push_auth_key_path' => env('APPLE_PUSH_AUTH_KEY_PATH', storage_path('app/wallet_pass/certificates/AuthKey.p8')),
    'apple_push_topic' => env('APPLE_PUSH_TOPIC', env('WALLET_PASS_TYPE_ID', '')),
    'apple_push_use_sandbox' => filter_var(env('APPLE_PUSH_USE_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
    'apple_push_timeout_seconds' => (int) env('APPLE_PUSH_TIMEOUT_SECONDS', 10),
    'allow_demo_user' => filter_var(env('WALLET_ADD_DEMO_USER', false), FILTER_VALIDATE_BOOLEAN),
];
