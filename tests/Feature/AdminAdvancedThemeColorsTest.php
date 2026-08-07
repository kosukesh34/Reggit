<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdvancedThemeColorsTest extends TestCase
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

    public function test_advanced_settings_メイン色入力がなく成功色を保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        ShopSettingStore::setValue('line_primary_color', '#111111', 'string', $project->id);
        ShopSettingStore::setValue('line_primary_dark', '#222222', 'string', $project->id);
        ShopSettingStore::setValue('line_success_color', '#059669', 'string', $project->id);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.advanced', ['project_key' => $project->project_key]))
            ->assertStatus(200)
            ->assertDontSee('name="line_primary_color"', false)
            ->assertDontSee('name="line_primary_dark"', false)
            ->assertSee('LIFFデザイン');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.advanced.update', ['project_key' => $project->project_key]), [
                'line_success_color' => '#10b981',
                'line_danger_color' => '#ef4444',
            ])
            ->assertRedirect();

        $theme = ShopSettingStore::getLineThemeColors($project->id);
        $this->assertSame('#111111', $theme['primary']);
        $this->assertSame('#222222', $theme['primary_dark']);
        $this->assertSame('#10b981', $theme['success']);
        $this->assertSame('#ef4444', $theme['danger']);
    }
}
