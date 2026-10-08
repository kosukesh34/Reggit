<?php

namespace Tests\Feature;

use Tests\TestCase;

class ClientDebugEndpointTest extends TestCase
{
    public function test_client_debug_is_disabled_by_default(): void
    {
        config(['app.client_debug_enabled' => false]);

        $this->postJson('/api/client-debug', ['event' => 'page_loaded'])
            ->assertNotFound();
    }

    public function test_client_debug_requires_configured_bearer_token(): void
    {
        config([
            'app.client_debug_enabled' => true,
            'app.client_debug_token' => 'test-token',
        ]);

        $this->postJson('/api/client-debug', ['event' => 'page_loaded'])
            ->assertUnauthorized();
    }

    public function test_client_debug_validates_and_accepts_bounded_payloads(): void
    {
        config([
            'app.client_debug_enabled' => true,
            'app.client_debug_token' => 'test-token',
        ]);

        $this->postJson('/api/client-debug', ['event' => 'page_loaded'], [
            'Authorization' => 'Bearer test-token',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->postJson('/api/client-debug', [
            'event' => 'page_loaded',
            'payload' => str_repeat('x', 4097),
        ], [
            'Authorization' => 'Bearer test-token',
        ])->assertUnprocessable();
    }
}
