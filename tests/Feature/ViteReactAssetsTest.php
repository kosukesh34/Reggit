<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViteReactAssetsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_liff画面にreactとviteの読み込みが含まれること(): void
    {
        $this->createProject();

        $response = $this->get('/liff?project_key=default');

        $response->assertStatus(200);
        $response->assertSee('id="react-frontend-root"', false);
        $response->assertSee('/build/assets/', false);
    }

    public function test_管理画面にreactとviteの読み込みが含まれること(): void
    {
        $project = $this->createProject();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $admin->projects()->sync([$project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('id="react-admin-root"', false);
        $response->assertSee('/build/assets/', false);
    }

    public function test_liffデザイナー画面がreact管理エントリで表示されること(): void
    {
        $project = $this->createProject();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $admin->projects()->sync([$project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('data-page-kind="liff-designer"', false);
        $response->assertSee('id="ldDesignerConfig"', false);
        $response->assertSee('id="react-admin-root"', false);
    }
}
