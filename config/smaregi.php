<?php

return [
    'client_id' => env('SMAREGI_CLIENT_ID', ''),
    'client_secret' => env('SMAREGI_CLIENT_SECRET', ''),
    'contract_id' => env('SMAREGI_CONTRACT_ID', 'sb_skx928r2'),
    'id_base_url' => env('SMAREGI_ID_BASE_URL', 'https://id.smaregi.dev'),
    'api_base_url' => env('SMAREGI_API_BASE_URL', 'https://api.smaregi.dev'),
    'scope' => env('SMAREGI_SCOPE', 'pos.stores:read pos.stores:write pos.products:read pos.products:write pos.transactions:read pos.transactions:write pos.customers:read pos.customers:write pos.stock:read pos.stock:write pos.staffs:read pos.suppliers:read pos.terminals:notify'),
    'webhook_secret' => env('SMAREGI_WEBHOOK_SECRET', ''),
    'webhook_secret_header' => env('SMAREGI_WEBHOOK_SECRET_HEADER', 'X-Smaregi-Webhook-Secret'),
    'webhook_run_sync_inline' => env('SMAREGI_WEBHOOK_RUN_SYNC_INLINE', env('APP_ENV', 'production') === 'local'),
    'inbound_sync_on_member_fetch' => env('SMAREGI_INBOUND_SYNC_ON_MEMBER_FETCH', true),
    'inbound_sync_on_member_fetch_ttl_seconds' => (int) env('SMAREGI_INBOUND_SYNC_ON_MEMBER_FETCH_TTL', 30),
];
