<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use App\Services\Admin\PublicImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private function loginSystemAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /** プロジェクト作成・更新フォームの必須項目（バリデーション通過用） */
    private function projectFormPayload(array $overrides = []): array
    {
        return array_merge([
            'project_key' => 'proj-test',
            'name' => 'Test Project',
            'description' => '',
            'is_active' => true,
            'plan' => 'normal',
            'line_liff_id' => '1234567890-abcd',
            'line_liff_url' => 'https://liff.line.me/1234',
            'frontend_header_text' => 'Test',
            'business_hours_start' => '10:00',
            'business_hours_end' => '22:00',
            'line_primary_color' => '#000000',
            'line_primary_dark' => '#333333',
            'line_success_color' => '#000000',
            'line_danger_color' => '#dc3545',
        ], $overrides);
    }

    public function test_store_プロジェクト作成時にロゴをアップロードできること(): void
    {
        Storage::fake('public');
        $this->loginSystemAdmin();
        $file = UploadedFile::fake()->image('project-logo.png', 64, 64);

        $response = $this->post('/system-admin/projects', $this->projectFormPayload([
            'project_key' => 'proj-logo',
            'name' => 'Logo Project',
            'frontend_logo' => $file,
        ]));

        $response->assertRedirect(route('system-admin.projects.index'));
        $project = Project::where('project_key', 'proj-logo')->first();
        $this->assertNotNull($project);
        $savedUrl = ShopSettingStore::getFrontendLogoUrl((int) $project->id);
        $this->assertNotNull($savedUrl);
        $this->assertStringContainsString('storage/'.PublicImageStorageService::DIR_FRONTEND_LOGO.'/'.$project->id, (string) $savedUrl);
    }

    public function test_edit_画面にロゴのurl入力がなくアップロードがあること(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create();

        $this->get('/system-admin/projects/'.$project->id.'/edit')
            ->assertStatus(200)
            ->assertSee('name="frontend_logo"', false)
            ->assertDontSee('name="frontend_logo_url"', false)
            ->assertDontSee('ロゴ画像URL', false);
    }

    public function test_index_プロジェクト一覧が表示されること(): void
    {
        $this->loginSystemAdmin();

        $response = $this->get('/system-admin/projects');

        $response->assertStatus(200);
    }

    public function test_store_プロジェクトが作成されること(): void
    {
        $this->loginSystemAdmin();

        $response = $this->post('/system-admin/projects', $this->projectFormPayload([
            'project_key' => 'proj-test',
            'name' => 'Test Project',
            'description' => 'Desc',
            'is_active' => true,
            'plan' => 'normal',
        ]));

        $response->assertRedirect(route('system-admin.projects.index'));
        $this->assertDatabaseHas('projects', ['project_key' => 'proj-test']);
    }

    public function test_update_プロジェクトが更新されること(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create(['name' => 'Old']);

        $response = $this->put('/system-admin/projects/'.$project->id, $this->projectFormPayload([
            'project_key' => $project->project_key,
            'name' => 'New',
            'description' => 'Updated',
            'is_active' => true,
            'plan' => 'premium',
        ]));

        $response->assertRedirect(route('system-admin.projects.index'));
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'New']);
    }

    public function test_index_ダッシュボードリンクが店舗ダッシュボードへ遷移すること(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create(['project_key' => 'proj-dash', 'is_active' => true]);

        $response = $this->get('/system-admin/projects');

        $response->assertOk();
        $response->assertSee(route('admin.dashboard', ['project_key' => $project->project_key]), false);
        $response->assertSee(route('liff.project', ['project_key' => $project->project_key]), false);

        $dashboard = $this->get('/admin/'.$project->project_key);
        $dashboard->assertRedirect(route('admin.dashboard', ['project_key' => $project->project_key]));
    }
}
