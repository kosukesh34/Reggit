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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // チャットは Firestore/Storage に直接読み書きする。サービスアカウントは FCM と共用。storage_bucket が空なら画像なし
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID', env('PUSH_FCM_PROJECT_ID')),
        'credentials' => env('FIREBASE_CREDENTIALS', env('PUSH_FCM_CREDENTIALS')),
        'web_api_key' => env('FIREBASE_WEB_API_KEY'),
        'web_app_id' => env('FIREBASE_WEB_APP_ID'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
        // ローカル検証用。設定すると画面が Firebase エミュレーターへ接続する
        'emulator_host' => env('FIREBASE_EMULATOR_HOST'),
    ],

];
