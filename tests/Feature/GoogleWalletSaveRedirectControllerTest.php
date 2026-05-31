<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GoogleWalletSaveRedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_長いsave_urlでもhtmlページを返し502にならないこと(): void
    {
        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );

        $longJwt = str_repeat('a', 4000);
        $saveUrl = 'https://pay.google.com/gp/v/save/'.$longJwt;
        Cache::put('google_wallet_save_url:validtoken', [
            'url' => $saveUrl,
            'project_id' => 1,
        ], now()->addMinutes(3));

        $response = $this->get('/google-wallet/save/validtoken?project_key=default');

        $response->assertStatus(200);
        $response->assertSee('Google Wallet を開いています', false);
        $response->assertSee('pay.google.com', false);
        $this->assertNull($response->headers->get('Location'));
    }

    public function test_キャッシュミス時は410を返すこと(): void
    {
        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );

        $response = $this->get('/google-wallet/save/missingtoken?project_key=default');

        $response->assertStatus(410);
    }
}
