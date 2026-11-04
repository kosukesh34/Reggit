<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\SlackHandler;

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            // Slack に送るときは LOG_STACK=daily,slack
            'channels' => explode(',', (string) env('LOG_STACK', 'daily')),
            // Slack に届かなくても、ログを書いた処理（リクエスト）は失敗させない
            'ignore_exceptions' => true,
        ],

        // エラー以上を Slack へ送る。ボットに chat:write を付け、通知先チャンネルに招待しておく
        'slack' => [
            'driver' => 'monolog',
            // トークン未設定の間は送らない（空トークンで毎回 Slack へ接続しない）
            'handler' => env('SLACK_BOT_USER_OAUTH_TOKEN') ? SlackHandler::class : NullHandler::class,
            'handler_with' => [
                'token' => (string) env('SLACK_BOT_USER_OAUTH_TOKEN'),
                'channel' => (string) env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
                'username' => 'Reggit ('.env('APP_ENV', 'production').')',
                'includeContextAndExtra' => true,
                // Slack が遅くても利用者の待ち時間を延ばさない
                'timeout' => 3.0,
                'connectionTimeout' => 3.0,
                'writingTimeout' => 3.0,
            ],
            'level' => env('LOG_SLACK_LEVEL', 'error'),
            'replace_placeholders' => true,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

];
