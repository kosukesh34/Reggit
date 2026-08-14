<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_dashboard_プロジェクト管理者がダッシュボードにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_dashboard_ダッシュボードにコンテンツが表示されること(): void
    {
        $project = $this->createProject();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('ダッシュボード', false);
        $response->assertSee('今日やること', false);
        $response->assertSee('今日・今月の数字', false);
        $response->assertSee('最近の注文', false);
        $response->assertSee('未処理注文', false);
        $response->assertSee('注文を確認', false);
        $response->assertDontSee('クイックメニュー', false);
        $response->assertDontSee('総商品数', false);
        $response->assertDontSee('総ユーザー数', false);
    }

    public function test_dashboard_サイドバーは日常操作と設定ハブに分かれていること(): void
    {
        $project = $this->createProject();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee($project->name, false);
        $response->assertSee('運用', false);
        $response->assertSee('設定', false);
        $response->assertSee(route('admin.settings.index', ['project_key' => $project->project_key], false), false);
        $response->assertDontSee('共通設定', false);
        $response->assertDontSee('アプリ・画面', false);
        $response->assertDontSee('予約設定', false);
        $response->assertDontSee('クーポン設定', false);
        $response->assertDontSee('注文（モバイル・店内）', false);
        $response->assertDontSee('注文・商品・画面導線', false);
    }
}
