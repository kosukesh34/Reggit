<?php

namespace Tests\Feature;

use App\Enums\SmaregiEvent;
use App\Jobs\SmaregiSyncInboundCustomerPointsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SmaregiWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_idp_アプリ契約通知を受け付けて200を返すこと(): void
    {
        $response = $this->postJson('/api/smaregi/idp', [
            'event' => SmaregiEvent::APP_SUBSCRIPTION,
            'action' => 'start',
            'date' => '2026-02-26',
            'contractId' => 'test_contract_001',
            'clientId' => 'client_84cf',
            'plan' => ['name' => 'スタンダード', 'price' => 3000],
            'options' => [],
        ], [
            'smaregi-event' => SmaregiEvent::APP_SUBSCRIPTION,
            'smaregi-contract-id' => 'test_contract_001',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('smaregi_contracts', [
            'contract_id' => 'test_contract_001',
            'status' => 'active',
        ]);
    }

    public function test_idp_ヘッダーがなくてもボディのeventとcontract_idで契約が保存されること(): void
    {
        $response = $this->postJson('/api/smaregi/idp', [
            'event' => SmaregiEvent::APP_SUBSCRIPTION,
            'action' => 'start',
            'date' => '2026-02-26',
            'contractId' => 'test_contract_body_only',
            'clientId' => 'client_84cf',
            'plan' => ['name' => 'スタンダード', 'price' => 3000],
            'options' => [],
        ], [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('smaregi_contracts', [
            'contract_id' => 'test_contract_body_only',
            'status' => 'active',
        ]);
    }

    public function test_idp_契約以外のイベントは200で契約を保存しないこと(): void
    {
        $response = $this->postJson('/api/smaregi/idp', [
            'contractId' => 'test_contract_002',
            'event' => 'pos:products',
            'action' => 'created',
        ], [
            'smaregi-event' => 'pos:products',
            'smaregi-contract-id' => 'test_contract_002',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('smaregi_contracts', ['contract_id' => 'test_contract_002']);
    }

    public function test_idp_urlにpos_customersが来た場合もポイント同期ジョブがディスパッチされること(): void
    {
        Bus::fake();

        $response = $this->postJson('/api/smaregi/idp', [
            'contractId' => 'test_contract_idp_webhook',
            'event' => 'pos:customers',
            'action' => 'edited',
            'customerIds' => ['101'],
        ], [
            'smaregi-event' => 'pos:customers',
            'smaregi-contract-id' => 'test_contract_idp_webhook',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200);
        Bus::assertDispatched(SmaregiSyncInboundCustomerPointsJob::class, function (SmaregiSyncInboundCustomerPointsJob $job): bool {
            return $job->contractId === 'test_contract_idp_webhook'
                && $job->customerIds === ['101']
                && $job->action === 'edited';
        });
    }

    public function test_idp_契約_i_dがない場合は400を返すこと(): void
    {
        $response = $this->postJson('/api/smaregi/idp', [], [
            'smaregi-event' => SmaregiEvent::APP_SUBSCRIPTION,
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_ge_tは200で空ボディを返すこと(): void
    {
        $this->get('/api/smaregi/webhook')->assertStatus(200);
    }

    public function test_webhook_apiプレフィックスなしのパスでも_pos_tで受信できること(): void
    {
        $this->postJson('/smaregi/webhook', [
            'contractId' => 'test_contract_no_api',
            'event' => 'pos:products',
            'action' => 'created',
        ], [
            'smaregi-event' => 'pos:products',
            'smaregi-contract-id' => 'test_contract_no_api',
            'Content-Type' => 'application/json',
        ])->assertStatus(200);
    }

    public function test_webhook_content_typeがjson以外でも生_jso_nなら解析できること(): void
    {
        $payload = json_encode([
            'contractId' => 'test_contract_plain_ct',
            'event' => 'pos:products',
            'action' => 'created',
        ], JSON_THROW_ON_ERROR);
        $response = $this->call('POST', '/api/smaregi/webhook', [], [], [], [
            'CONTENT_TYPE' => 'text/plain; charset=utf-8',
            'HTTP_smaregi-event' => 'pos:products',
            'HTTP_smaregi-contract-id' => 'test_contract_plain_ct',
        ], $payload);
        $response->assertStatus(200);
    }

    public function test_webhook_イベントヘッダーがあれば200を返すこと(): void
    {
        $response = $this->postJson('/api/smaregi/webhook', [
            'contractId' => 'test_contract_003',
            'event' => 'pos:products',
            'action' => 'created',
        ], [
            'smaregi-event' => 'pos:products',
            'smaregi-contract-id' => 'test_contract_003',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200);
    }

    public function test_webhook_ヘッダーがなくてもボディのeventがあれば200を返すこと(): void
    {
        $response = $this->postJson('/api/smaregi/webhook', [
            'contractId' => 'test_contract_004',
            'event' => 'pos:products',
            'action' => 'created',
        ], [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200);
    }

    public function test_webhook_eventがヘッダーにもボディにもない場合は400を返すこと(): void
    {
        $response = $this->postJson('/api/smaregi/webhook', [
            'contractId' => 'test_contract_005',
            'action' => 'created',
        ], [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
    }
}
