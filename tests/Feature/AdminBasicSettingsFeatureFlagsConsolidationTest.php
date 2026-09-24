<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminBasicSettingsFeatureFlagsConsolidationTest extends TestCase
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

    public function test_basic_settings_に機能フラグフォームがないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.basic', ['project_key' => $project->project_key]))
            ->assertStatus(200)
            ->assertDontSee('name="feature_flags[', false);
    }

    public function test_feature_flags_update_ルートが存在しないこと(): void
    {
        $this->assertFalse(
            Route::has('admin.settings.feature-flags.update')
        );
    }
}
