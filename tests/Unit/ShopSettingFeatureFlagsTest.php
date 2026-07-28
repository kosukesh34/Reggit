<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ShopSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopSettingFeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_setFeatureFlags_falseの値はOFFとして保存されること(): void
    {
        $project = $this->createProject();
        $flags = ShopSetting::getFeatureFlags($project->id);
        $flags['feature_coupons_enabled'] = false;
        $flags['feature_orders_enabled'] = true;

        ShopSetting::setFeatureFlags($flags, $project->id);

        $this->assertFalse(ShopSetting::isFeatureEnabled('feature_coupons_enabled', $project->id));
        $this->assertTrue(ShopSetting::isFeatureEnabled('feature_orders_enabled', $project->id));
    }

    public function test_setFeatureFlags_未指定キーはOFFになること(): void
    {
        $project = $this->createProject();

        ShopSetting::setFeatureFlags([
            'feature_orders_enabled' => '1',
            'feature_members_enabled' => true,
        ], $project->id);

        $this->assertTrue(ShopSetting::isFeatureEnabled('feature_orders_enabled', $project->id));
        $this->assertTrue(ShopSetting::isFeatureEnabled('feature_members_enabled', $project->id));
        $this->assertFalse(ShopSetting::isFeatureEnabled('feature_coupons_enabled', $project->id));
        $this->assertFalse(ShopSetting::isFeatureEnabled('feature_gacha_enabled', $project->id));
    }

    public function test_setFeatureFlags_文字列0はOFFになること(): void
    {
        $project = $this->createProject();
        $flags = ShopSetting::getFeatureFlags($project->id);
        $flags['feature_gallery_enabled'] = '0';

        ShopSetting::setFeatureFlags($flags, $project->id);

        $this->assertFalse(ShopSetting::isFeatureEnabled('feature_gallery_enabled', $project->id));
    }
}
