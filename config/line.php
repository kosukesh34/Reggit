<?php

return [
    'liff_id' => env('LINE_LIFF_ID', ''),
    'liff_url' => env('LINE_LIFF_URL', ''),
    'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN', ''),
    'channel_secret' => env('LINE_CHANNEL_SECRET', ''),
    'channel_id' => env('LINE_CHANNEL_ID', ''),
    'verify_liff_id_token' => filter_var(env('LINE_VERIFY_LIFF_ID_TOKEN', true), FILTER_VALIDATE_BOOLEAN),
    /** LINE Flex 画像用。相対/storage URL を絶対化するときのベース（未設定時は APP_URL） */
    'notification_image_base_url' => env('LINE_NOTIFICATION_IMAGE_BASE_URL', ''),
    /** 保存時に LIFF Module mode を自動有効化し、共有・更新メニュー（アクションボタン）を非表示にする */
    'auto_enable_liff_module_mode' => env('LINE_LIFF_AUTO_MODULE_MODE', true),
];
