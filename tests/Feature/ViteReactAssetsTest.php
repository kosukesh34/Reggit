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
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );
        if ($project->plan !== 'premium') {
            $project->update(['plan' => 'premium']);
        }

        return $project;
    }

    public function test_liff画面にreactとviteの読み込みが含まれること(): void
    {
        $this->createProject();

        $response = $this->get('/liff?project_key=default');

        $response->assertStatus(200);
        $response->assertSee('id="react-frontend-root"', false);
        $response->assertSee('css/frontend/components/29-system-ui-customization.css', false);
        $response->assertSee('css/frontend/components/38-liff-fixed-ui.css', false);
        $response->assertSee('/build/assets/', false);
    }

    public function test_ネイティブUAのliff画面でも機能CSSとシェルが含まれること(): void
    {
        $this->createProject();

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Reggit/1.0',
        ])->get('/liff?project_key=default');

        $response->assertStatus(200);
        $response->assertSee('native-app-embed', false);
        $response->assertSee('id="app-container"', false);
        $response->assertSee('css/frontend/components/22-chrome-accent.css', false);
        $response->assertSee('css/frontend/components/29-system-ui-customization.css', false);
        $response->assertSee('css/frontend/components/38-liff-fixed-ui.css', false);
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
