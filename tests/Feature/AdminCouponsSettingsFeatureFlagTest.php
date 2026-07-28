<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\ShopSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponsSettingsFeatureFlagTest extends TestCase
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

    public function test_coupons_settings_無効保存時に他機能フラグを壊さないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        ShopSetting::setFeatureFlags([
            'feature_home_enabled' => true,
            'feature_orders_enabled' => true,
            'feature_reservations_enabled' => false,
            'feature_queue_enabled' => false,
            'feature_stamps_enabled' => true,
            'feature_coupons_enabled' => true,
            'feature_members_enabled' => true,
            'feature_announcements_enabled' => false,
            'feature_payments_enabled' => true,
            'feature_gallery_enabled' => false,
            'feature_gacha_enabled' => false,
            'feature_scratch_enabled' => false,
        ], $project->id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.coupons.update', ['project_key' => $project->project_key]), [
                'feature_coupons_enabled' => '0',
                'coupon_default_image_url' => null,
            ])
            ->assertRedirect(route('admin.settings.coupons', ['project_key' => $project->project_key]));

        $this->assertFalse(ShopSetting::isFeatureEnabled('feature_coupons_enabled', $project->id));
        $this->assertTrue(ShopSetting::isFeatureEnabled('feature_orders_enabled', $project->id));
        $this->assertFalse(ShopSetting::isFeatureEnabled('feature_reservations_enabled', $project->id));
        $this->assertFalse(ShopSetting::isFeatureEnabled('feature_gallery_enabled', $project->id));
        $this->assertFalse(ShopSetting::isFeatureEnabled('feature_announcements_enabled', $project->id));
        $this->assertTrue(ShopSetting::isFeatureEnabled('feature_stamps_enabled', $project->id));
    }
}
