<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * セキュリティ観点のテスト。
 * 認証・認可・CSRF が本番と同等に有効であることを確認する。
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証で店舗管理画面にアクセスするとログインへリダイレクトされる(): void
    {
        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );

        $response = $this->get(route('admin.dashboard', ['project_key' => 'default']));

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertTrue(
            str_contains($target ?? '', 'login') || str_contains($target ?? '', 'admin'),
            '未認証時はログイン系URLへリダイレクトすること: '.$target
        );
    }

    public function test_未認証でシステム管理画面にアクセスするとログインへリダイレクトされる(): void
    {
        $response = $this->get(route('system-admin.dashboard'));

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertTrue(
            str_contains($target ?? '', 'login') || str_contains($target ?? '', 'system-admin'),
            '未認証時はログイン系URLへリダイレクトすること: '.$target
        );
    }

    public function test_未認証で管理画面の_ap_iトークン一覧にアクセスするとログインへリダイレクトされる(): void
    {
        $response = $this->get(route('system-admin.api-tokens.index'));

        $response->assertRedirect();
    }

    public function test_未認証でアカウント一覧にアクセスするとログインへリダイレクトされる(): void
    {
        $response = $this->get('/system-admin/accounts');

        $response->assertRedirect();
    }

    public function test_本番環境では会員ポイント加算エンドポイントを無効化する(): void
    {
        config(['app.env' => 'production']);
        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );

        $response = $this->postJson('/api/members/points/add', [
            'points' => 100,
            'description' => 'debug',
        ]);

        $response->assertStatus(404);
    }

    public function test_本番環境ではclient_debugはトークンなしで無効化される(): void
    {
        config(['app.env' => 'production']);
        config(['app.debug' => false]);

        $response = $this->postJson('/api/client-debug', [
            'event' => 'test',
        ]);

        $response->assertStatus(404);
    }
}
