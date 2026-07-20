<?php

return [
    'liff_id' => env('LINE_LIFF_ID', ''),
    'liff_url' => env('LINE_LIFF_URL', ''),
    'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN', ''),
    'channel_secret' => env('LINE_CHANNEL_SECRET', ''),
    /** 保存時に LIFF Module mode を自動有効化し、共有・更新メニュー（アクションボタン）を非表示にする */
    'auto_enable_liff_module_mode' => env('LINE_LIFF_AUTO_MODULE_MODE', true),
];
