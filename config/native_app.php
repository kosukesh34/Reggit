<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Native App User-Agent Token
    |--------------------------------------------------------------------------
    |
    | ネイティブアプリ WebView の User-Agent に付与する識別子。
    | Skip の NATIVE_APP_USER_AGENT_TOKEN（Skip.env）と一致させる。
    | 例: Reggit/1.0 (ios)
    |
    */

    'user_agent_token' => env('NATIVE_APP_USER_AGENT_TOKEN', 'Reggit'),

    /*
    |--------------------------------------------------------------------------
    | Native App URL Scheme
    |--------------------------------------------------------------------------
    |
    | Wallet 追加後の「アプリに戻る」等で使うカスタム URL スキーム。
    | Skip の NATIVE_APP_URL_SCHEME（Skip.env）と一致させる。
    |
    */

    'url_scheme' => env('NATIVE_APP_URL_SCHEME', 'reggit'),

    /*
    |--------------------------------------------------------------------------
    | Mobile Order Feature Label
    |--------------------------------------------------------------------------
    |
    | 注文種別「mobile」および管理画面の機能フラグ表示名。
    |
    */

    'order_type_mobile_label' => env('NATIVE_APP_ORDER_TYPE_MOBILE_LABEL', 'Reggit'),

];
