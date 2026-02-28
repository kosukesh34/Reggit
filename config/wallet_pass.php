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
    'allow_demo_user' => filter_var(env('WALLET_ADD_DEMO_USER', false), FILTER_VALIDATE_BOOLEAN),
];
