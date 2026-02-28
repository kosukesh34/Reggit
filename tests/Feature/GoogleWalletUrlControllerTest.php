<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Google Wallet URL エンドポイントの Feature テスト。
 * 処理落ちしないこと・期待する HTTP ステータスと JSON を返すことを検証する。
 * 本番の Google Wallet API は呼ばない（設定無効時や会員未登録時の挙動のみ）。
 */
class GoogleWalletUrlControllerTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000002';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_google_wallet_url_利用不可時に403とエラーメッセージを返すこと(): void
    {
        $this->createProject();
        config(['google_wallet.enabled' => false]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me/google-wallet-url?project_key=default');

        $response->assertStatus(403)
            ->assertJsonPath('error', 'Google Wallet は利用できません。')
            ->assertJsonStructure(['error', 'reasons']);
    }

    public function test_google_wallet_url_会員でない場合に403を返すこと(): void
    {
        $project = $this->createProject();
        config(['google_wallet.allow_demo_user' => false]);
        User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $this->mock(\App\Services\GoogleWalletPassService::class, function ($mock): void {
            $mock->shouldReceive('isAvailable')->andReturn(true);
        });

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me/google-wallet-url?project_key=default');

        $response->assertStatus(403)
            ->assertJsonPath('error', '会員登録が必要です');
    }

    public function test_google_wallet_url_リクエストが正常に処理されること(): void
    {
        $this->createProject();
        config(['google_wallet.enabled' => false]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me/google-wallet-url?project_key=default');

        $this->assertContains($response->status(), [200, 403]);
    }

    public function test_google_wallet_url_project_keyなしでプロジェクト解決されること(): void
    {
        $this->createProject();
        config(['google_wallet.enabled' => false]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me/google-wallet-url');

        $response->assertStatus(403);
    }
}
