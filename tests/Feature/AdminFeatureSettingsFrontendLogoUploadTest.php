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

class AdminFeatureSettingsFrontendLogoUploadTest extends TestCase
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

    public function test_機能設定でヘッダーロゴをアップロードできること(): void
    {
        Storage::fake('public');
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $file = UploadedFile::fake()->image('logo.png', 64, 64);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.features.update', ['project_key' => $project->project_key]), [
                'features' => [
                    'feature_orders_enabled' => '1',
                    'feature_members_enabled' => '1',
                ],
                'frontend_logo' => $file,
                'frontend_header_text' => 'Demo',
            ])
            ->assertRedirect();

        $savedUrl = ShopSettingStore::getFrontendLogoUrl($project->id);
        $this->assertNotNull($savedUrl);
        $this->assertStringContainsString('storage/'.PublicImageStorageService::DIR_FRONTEND_LOGO.'/'.$project->id, (string) $savedUrl);
        Storage::disk('public')->assertExists(
            PublicImageStorageService::DIR_FRONTEND_LOGO.'/'.$project->id.'/'.$file->hashName()
        );
    }

    public function test_機能設定でヘッダーロゴをクリアできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        ShopSettingStore::setValue('frontend_logo_url', 'https://example.com/old-logo.png', 'string', $project->id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.features.update', ['project_key' => $project->project_key]), [
                'features' => [
                    'feature_orders_enabled' => '1',
                ],
                'frontend_logo_clear' => '1',
                'frontend_header_text' => '',
            ])
            ->assertRedirect();

        $this->assertSame('', (string) (ShopSettingStore::getFrontendLogoUrl($project->id) ?? ''));
    }

    public function test_機能設定でロゴ未指定なら既存値を維持すること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $existing = 'https://example.com/keep-logo.png';
        ShopSettingStore::setValue('frontend_logo_url', $existing, 'string', $project->id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.features.update', ['project_key' => $project->project_key]), [
                'features' => [
                    'feature_orders_enabled' => '1',
                ],
                'frontend_header_text' => 'Keep',
            ])
            ->assertRedirect();

        $this->assertSame($existing, ShopSettingStore::getFrontendLogoUrl($project->id));
    }

    public function test_機能設定画面にロゴのドロップゾーンがあること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.features', ['project_key' => $project->project_key]))
            ->assertStatus(200)
            ->assertSee('name="frontend_logo"', false)
            ->assertDontSee('name="frontend_logo_url"', false)
            ->assertDontSee('placeholder="https://example.com/logo.png"', false);
    }
}
