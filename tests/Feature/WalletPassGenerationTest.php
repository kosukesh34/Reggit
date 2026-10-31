<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\WalletPassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WalletPassGenerationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, bool|string> */
    private function walletPassTestConfig(): array
    {
        $fixtureDir = base_path('tests/fixtures/wallet_pass');

        return [
            'wallet_pass.enabled' => true,
            'wallet_pass.team_id' => 'T29T2L39T5',
            'wallet_pass.type_id' => 'pass.IDNect',
            'wallet_pass.cert_path' => $fixtureDir.'/pass.cer',
            'wallet_pass.key_path' => $fixtureDir.'/test.key',
            // テスト用自己署名証明書では WWDR も同一証明書を指定
            'wallet_pass.wwdr_path' => $fixtureDir.'/pass.cer',
            'wallet_pass.assets_path' => storage_path('app/wallet_pass/assets'),
        ];
    }

    public function test_apple_wallet用pkpassを生成できること(): void
    {
        config($this->walletPassTestConfig());

        $project = Project::factory()->create(['name' => 'Wallet Test']);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'WALLET001',
            'rank' => 'bronze',
        ]);

        $service = app(WalletPassService::class);
        $content = $service->generateForMember($member);

        $this->assertNotNull($content, $service->getLastError() ?? 'pkpass generation failed');
        $this->assertStringStartsWith('PK', $content);
    }

    public function test_wallet_pass_urlは現在のリクエストホストで発行されること(): void
    {
        config($this->walletPassTestConfig());

        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U00000000000000000000000000000002',
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'WALLETURL001',
        ]);

        $response = $this->withHeaders([
            'X-Line-User-Id' => 'U00000000000000000000000000000002',
            'X-Forwarded-Host' => 'wallet-test.example.test',
            'X-Forwarded-Proto' => 'https',
        ])
            ->get('/api/member-wallet/pass-url?project_key=default');

        $response->assertStatus(200);
        $url = (string) $response->json('url');
        $this->assertStringStartsWith('https://wallet-test.example.test/wallet-add?', $url);
        $this->assertStringContainsString('pass_token=', $url);
    }

    public function test_wallet_add_pass_tokenからpkpassダウンロードurlを解決できること(): void
    {
        config($this->walletPassTestConfig());

        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U00000000000000000000000000000003',
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'WALLETURL002',
        ]);

        $issueResponse = $this->withHeaders([
            'X-Line-User-Id' => 'U00000000000000000000000000000003',
            'X-Forwarded-Host' => 'wallet-test.example.test',
            'X-Forwarded-Proto' => 'https',
        ])
            ->get('/api/member-wallet/pass-url?project_key=default');

        $issueResponse->assertStatus(200);
        $walletAddUrl = (string) $issueResponse->json('url');
        parse_str((string) parse_url($walletAddUrl, PHP_URL_QUERY), $query);
        $passToken = (string) ($query['pass_token'] ?? '');
        $this->assertNotEmpty($passToken);
        $this->assertNotNull(Cache::get('wallet_pass_token:'.$passToken));

        $response = $this->withHeaders([
            'X-Forwarded-Host' => 'wallet-test.example.test',
            'X-Forwarded-Proto' => 'https',
        ])->get('/wallet-add?'.http_build_query([
            'pass_token' => $passToken,
            'project_key' => 'default',
            'type' => 'apple',
        ]));

        $response->assertStatus(200);
        $response->assertSee('id="walletAddButton"', false);
        $response->assertSee('member-wallet', false);
        $response->assertSee($passToken, false);
    }

    public function test_wallet_pass_tokenはttl内で複数回ダウンロードできること(): void
    {
        config($this->walletPassTestConfig());

        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U00000000000000000000000000000004',
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'WALLETURL003',
        ]);

        $issueResponse = $this->withHeaders([
            'X-Line-User-Id' => 'U00000000000000000000000000000004',
            'X-Forwarded-Host' => 'wallet-test.example.test',
            'X-Forwarded-Proto' => 'https',
        ])
            ->get('/api/member-wallet/pass-url?project_key=default');

        $walletAddUrl = (string) $issueResponse->json('url');
        parse_str((string) parse_url($walletAddUrl, PHP_URL_QUERY), $query);
        $passToken = (string) ($query['pass_token'] ?? '');
        $this->assertNotEmpty($passToken);

        $downloadUrl = '/api/member-wallet/pass?project_key=default&token='.$passToken;

        $first = $this->withHeaders([
            'X-Forwarded-Host' => 'wallet-test.example.test',
            'X-Forwarded-Proto' => 'https',
        ])->get($downloadUrl);
        $first->assertStatus(200);
        $first->assertHeader('Content-Type', 'application/vnd.apple.pkpass');

        $second = $this->withHeaders([
            'X-Forwarded-Host' => 'wallet-test.example.test',
            'X-Forwarded-Proto' => 'https',
        ])->get($downloadUrl);
        $second->assertStatus(200);
        $second->assertHeader('Content-Type', 'application/vnd.apple.pkpass');
    }

    public function test_wallet_add_中間ページでwallet_urlが有効なリンクになること(): void
    {
        config(['app.url' => 'https://wallet-test.example.test']);
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
        app(LineSettingStore::class)->setLineLiffId('2008962885-AbCdEfGh', (int) $project->id);
        $walletUrl = 'https://wallet-test.example.test/api/member-wallet/pass?project_key=default&token=test';

        $response = $this->withServerVariables(['HTTP_HOST' => 'wallet-test.example.test'])
            ->get('/wallet-add?'.http_build_query([
                'wallet_url' => $walletUrl,
                'return_url' => 'https://wallet-test.example.test/liff/default',
                'return_target' => 'line',
                'type' => 'apple',
                'project_key' => 'default',
            ]));

        $response->assertStatus(200);
        $response->assertSee('member-wallet', false);
        $response->assertSee('token=test', false);
        $response->assertSee('id="walletAddButton"', false);
        $response->assertSee('https://liff.line.me/2008962885-AbCdEfGh', false);
        $response->assertSee('liff.state', false);
        $response->assertSee('LINEに戻る', false);
    }
}
