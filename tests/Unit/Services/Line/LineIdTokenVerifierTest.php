<?php

namespace Tests\Unit\Services\Line;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Project;
use App\Services\Line\LineIdTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LineIdTokenVerifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifyはsubを返すこと(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        ShopSettingStore::setLineLiffId('1234567890-AbCdEfGh', (int) $project->id);
        Http::fake([
            'https://api.line.me/oauth2/v2.1/verify' => Http::response([
                'sub' => 'U00000000000000000000000000000011',
                'aud' => '1234567890',
            ], 200),
        ]);

        $sub = app(LineIdTokenVerifier::class)->verify('id-token', (int) $project->id);

        $this->assertSame('U00000000000000000000000000000011', $sub);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.line.me/oauth2/v2.1/verify'
                && $request['id_token'] === 'id-token'
                && $request['client_id'] === '1234567890';
        });
    }

    public function test_verifyは不正なsubならnullであること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        ShopSettingStore::setLineLiffId('1234567890-AbCdEfGh', (int) $project->id);
        Http::fake([
            'https://api.line.me/oauth2/v2.1/verify' => Http::response([
                'sub' => 'not-a-line-id',
            ], 200),
        ]);

        $this->assertNull(app(LineIdTokenVerifier::class)->verify('id-token', (int) $project->id));
    }

    public function test_verifyは通信エラーならnullであること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        ShopSettingStore::setLineLiffId('1234567890-AbCdEfGh', (int) $project->id);
        Http::fake([
            'https://api.line.me/oauth2/v2.1/verify' => Http::response([
                'error_description' => 'Invalid IdToken.',
            ], 400),
        ]);

        $this->assertNull(app(LineIdTokenVerifier::class)->verify('bad-token', (int) $project->id));
    }
}
