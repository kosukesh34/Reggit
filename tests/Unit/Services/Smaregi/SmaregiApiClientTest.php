<?php

namespace Tests\Unit\Services\Smaregi;

use App\Services\Smaregi\SmaregiApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ReflectionProperty;
use Tests\TestCase;

class SmaregiApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'smaregi.scope' => 'pos.stores:read',
        ]);
    }

    public function test_request開始時に前回のエラー詳細がクリアされること(): void
    {
        $client = new SmaregiApiClient;
        $detailProp = new ReflectionProperty(SmaregiApiClient::class, 'lastErrorDetail');
        $statusProp = new ReflectionProperty(SmaregiApiClient::class, 'lastHttpStatus');
        $detailProp->setValue($client, 'stale-error');
        $statusProp->setValue($client, 500);

        Http::fake([
            'https://id.smaregi.dev/*' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $this->assertNull($client->request('contract-1', 'GET', 'pos/stores'));
        $this->assertNull($client->getLastErrorDetail());
        $this->assertNull($client->getLastHttpStatus());
    }

    public function test_scoped登録なら同一解決内でclientを共有すること(): void
    {
        $first = app(SmaregiApiClient::class);
        $second = app(SmaregiApiClient::class);

        $this->assertSame($first, $second);
    }
}
