<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\ShopSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsMenuTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(int $projectId): AdminUser
    {
        return AdminUser::create([
            'login_id' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'name' => 'Admin',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $projectId,
        ]);
    }

    public function test_menu_settings_メニュー設定画面にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project->id);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.menu', ['project_key' => $project->project_key]));

        $response->assertOk()
            ->assertSee('メニュー設定');
    }

    public function test_menu_settings_メニュー設定を保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project->id);

        $payload = [
            'menu_items' => [
                ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-house', 'enabled' => 1, 'order' => 0],
                ['tab' => 'products', 'label' => '商品', 'icon' => 'fa-utensils', 'enabled' => 1, 'order' => 1],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.menu.update', ['project_key' => $project->project_key]), $payload);

        $response->assertRedirect();
        $stored = ShopSetting::getFrontendMenuItems($project->id);
        $this->assertSame('home', $stored[0]['tab']);
        $this->assertSame('products', $stored[1]['tab']);
    }
}
