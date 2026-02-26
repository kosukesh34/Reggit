<?php

return [

    /*
    |--------------------------------------------------------------------------
    | スマレジ・プラットフォームAPI 設定
    |--------------------------------------------------------------------------
    | スマレジ・デベロッパーズで発行されたクライアント情報とエンドポイントを設定します。
    | 利用者契約通知先URL: {APP_URL}/api/smaregi/idp
    | Webhook送信先: {APP_URL}/api/smaregi/webhook
    */

    'client_id' => env('SMAREGI_CLIENT_ID', ''),
    'client_secret' => env('SMAREGI_CLIENT_SECRET', ''),

    /** 実接続テストやローカルで利用する契約ID（任意）。未設定時は DB のプロジェクト紐付けを参照 */
    'contract_id' => env('SMAREGI_CONTRACT_ID', ''),

    'id_base_url' => env('SMAREGI_ID_BASE_URL', 'https://id.smaregi.dev'),
    'api_base_url' => env('SMAREGI_API_BASE_URL', 'https://api.smaregi.dev'),

    'scope' => env('SMAREGI_SCOPE', 'pos.stores:read pos.stores:write pos.products:read pos.products:write pos.transactions:read pos.transactions:write pos.customers:read pos.customers:write pos.stock:read pos.stock:write pos.staffs:read pos.suppliers:read pos.terminals:notify'),

    'webhook_secret' => env('SMAREGI_WEBHOOK_SECRET', ''),
    'webhook_secret_header' => env('SMAREGI_WEBHOOK_SECRET_HEADER', 'X-Smaregi-Webhook-Secret'),

];
