<?php

namespace Tests\Feature;

use App\Enums\SmaregiEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_idp_不正なイベントの場合は400を返すこと(): void
    {
        $response = $this->postJson('/api/smaregi/idp', [
            'contractId' => 'test_contract_002',
        ], [
            'smaregi-event' => 'InvalidEvent',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseMissing('smaregi_contracts', ['contract_id' => 'test_contract_002']);
    }

    public function test_idp_契約_i_dがない場合は400を返すこと(): void
    {
        $response = $this->postJson('/api/smaregi/idp', [], [
            'smaregi-event' => SmaregiEvent::APP_SUBSCRIPTION,
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
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

    public function test_webhook_イベントヘッダーがない場合は400を返すこと(): void
    {
        $response = $this->postJson('/api/smaregi/webhook', [
            'contractId' => 'test_contract_004',
            'event' => 'pos:products',
        ], [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
    }
}
