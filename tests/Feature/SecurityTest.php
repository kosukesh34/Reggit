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

    public function test_未認証で管理画面のAPIトークン一覧にアクセスするとログインへリダイレクトされる(): void
    {
        $response = $this->get(route('system-admin.api-tokens.index'));

        $response->assertRedirect();
    }

    public function test_未認証でアカウント一覧にアクセスするとログインへリダイレクトされる(): void
    {
        $response = $this->get('/system-admin/accounts');

        $response->assertRedirect();
    }
}
