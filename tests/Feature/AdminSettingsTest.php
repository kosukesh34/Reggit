<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\ShopSetting;
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

    public function test_settings_機能設定を更新できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.features.update', ['project_key' => $project->project_key]), [
                'features' => [
                    'feature_home_enabled' => '1',
                    'feature_orders_enabled' => '1',
                ],
                'admin_sidebar_color' => '#2c3e50',
                'frontend_header_text' => 'テスト店舗',
            ]);

        $response->assertRedirect(route('admin.settings.features', ['project_key' => $project->project_key]));
        $response->assertSessionHas('success');
    }

    public function test_settings_プロフィール設定を更新できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $defaults = ShopSetting::defaultProfileFieldSettings();
        $smaregiFields = [];
        foreach ($defaults['smaregi_fields'] as $key => $field) {
            $smaregiFields[$key] = [
                'enabled' => true,
                'required' => $field['required'] ?? false,
            ];
        }

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.profile.update', ['project_key' => $project->project_key]), [
                'smaregi_fields' => $smaregiFields,
                'custom_fields' => [
                    ['key' => 'nickname', 'label' => 'ニックネーム', 'required' => false],
                ],
            ]);

        $response->assertRedirect(route('admin.settings.profile', ['project_key' => $project->project_key]));
        $response->assertSessionHas('success');

        $saved = ShopSetting::getProfileFieldSettings($project->id);
        $this->assertSame('ニックネーム', $saved['custom_fields'][0]['label'] ?? null);
    }

    public function test_settings_Wallet設定を更新できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.wallet.update', ['project_key' => $project->project_key]), [
                'wallet_google_program_logo_url' => 'https://example.com/logo.png',
                'wallet_google_hero_image_url' => 'https://example.com/hero.png',
            ]);

        $response->assertRedirect(route('admin.settings.wallet', ['project_key' => $project->project_key]));
        $response->assertSessionHas('success');

        $this->assertSame(
            'https://example.com/logo.png',
            ShopSetting::getWalletGoogleProgramLogoUrl($project->id)
        );
    }
}
