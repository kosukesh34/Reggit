<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\ShopSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiffDesignerTest extends TestCase
{
    use RefreshDatabase;

    public function test_liff_designer_管理者がLIFFデザイナー画面にアクセスできること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );

        $admin = AdminUser::create([
            'login_id' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'name' => 'Admin',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $response->assertOk();
        $response->assertSee('LIFFデザイン');
    }

    public function test_liff_designer_レイアウトを保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );

        $admin = AdminUser::create([
            'login_id' => 'admin2',
            'email' => 'admin2@example.com',
            'password' => 'password',
            'name' => 'Admin2',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $layouts = [
            'home' => [
                ['type' => 'text', 'title' => 'Hello', 'body' => 'World'],
                ['type' => 'slider', 'title' => 'Slides', 'slider_images' => ['https://example.com/a.jpg']],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode($layouts),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $stored = ShopSetting::getLiffTabLayouts($project->id);
        $this->assertSame($layouts, $stored);
    }
}
