<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\ShopSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLotteryFeatureFlagSourceTest extends TestCase
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

    public function test_features_settings_保存でガチャフラグを消さないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        ShopSetting::setValue('feature_gacha_enabled', '1', 'boolean', $project->id);
        ShopSetting::setValue('feature_scratch_enabled', '1', 'boolean', $project->id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.features.update', ['project_key' => $project->project_key]), [
                'features' => [
                    'feature_orders_enabled' => '1',
                    'feature_members_enabled' => '1',
                ],
            ])
            ->assertRedirect();

        $this->assertTrue(ShopSetting::isFeatureEnabled('feature_gacha_enabled', $project->id));
        $this->assertTrue(ShopSetting::isFeatureEnabled('feature_scratch_enabled', $project->id));
        $this->assertTrue(ShopSetting::isFeatureEnabled('feature_orders_enabled', $project->id));
    }

    public function test_feature_flag_labels_にガチャが含まれないこと(): void
    {
        $labels = ShopSetting::featureFlagLabels();
        $this->assertArrayNotHasKey('feature_gacha_enabled', $labels);
        $this->assertArrayNotHasKey('feature_scratch_enabled', $labels);
    }
}
