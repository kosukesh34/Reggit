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

    /** デフォルト契約ID（常に固定）。未設定時はこの値を使用。env で SMAREGI_CONTRACT_ID を指定すれば上書き可能 */
    'contract_id' => env('SMAREGI_CONTRACT_ID', 'sb_skx928r2'),

    'id_base_url' => env('SMAREGI_ID_BASE_URL', 'https://id.smaregi.dev'),
    'api_base_url' => env('SMAREGI_API_BASE_URL', 'https://api.smaregi.dev'),

    'scope' => env('SMAREGI_SCOPE', 'pos.stores:read pos.stores:write pos.products:read pos.products:write pos.transactions:read pos.transactions:write pos.customers:read pos.customers:write pos.stock:read pos.stock:write pos.staffs:read pos.suppliers:read pos.terminals:notify'),

    'webhook_secret' => env('SMAREGI_WEBHOOK_SECRET', ''),
    'webhook_secret_header' => env('SMAREGI_WEBHOOK_SECRET_HEADER', 'x-sdsch-idnect'),

    /**
     * true のとき Webhook 受信直後にポイント同期ジョブを同期的に実行する（local 向け）。
     * false のとき afterResponse で非同期実行（本番向け）。
     */
    'webhook_run_sync_inline' => env('SMAREGI_WEBHOOK_RUN_SYNC_INLINE', env('APP_ENV', 'production') === 'local'),

    /**
     * members/me 取得時にスマレジからポイントを取り込む（Webhook 未到達時のフォールバック）。
     */
    'inbound_sync_on_member_fetch' => env('SMAREGI_INBOUND_SYNC_ON_MEMBER_FETCH', true),

    /** 会員ごとの取り込み間隔（秒）。0 で毎回 API を呼ぶ。 */
    'inbound_sync_on_member_fetch_ttl_seconds' => (int) env('SMAREGI_INBOUND_SYNC_ON_MEMBER_FETCH_TTL', 30),

];
