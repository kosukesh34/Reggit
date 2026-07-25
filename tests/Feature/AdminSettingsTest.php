<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    public function test_settings_基本設定ページにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.basic', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_settings_注文LINE通知トグルを保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.basic.update', ['project_key' => $project->project_key]), [
                'business_hours_start' => '10:00',
                'business_hours_end' => '22:00',
                'order_mode' => 'both',
            ])
            ->assertRedirect();

        $this->assertFalse(\App\Models\ShopSetting::isOrderLineNotifyEnabled((int) $project->id));
        $this->assertFalse(\App\Models\ShopSetting::isOrderReadyLineNotifyEnabled((int) $project->id));

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.basic.update', ['project_key' => $project->project_key]), [
                'business_hours_start' => '10:00',
                'business_hours_end' => '22:00',
                'order_mode' => 'both',
                'order_line_notify_enabled' => '1',
                'order_ready_line_notify_enabled' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue(\App\Models\ShopSetting::isOrderLineNotifyEnabled((int) $project->id));
        $this->assertTrue(\App\Models\ShopSetting::isOrderReadyLineNotifyEnabled((int) $project->id));
    }

    public function test_settings_詳細設定ページにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.advanced', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_settings_ランク設定ページにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.ranks', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }
}
