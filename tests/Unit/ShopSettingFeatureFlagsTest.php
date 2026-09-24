<?php

namespace Tests\Unit;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Project;
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

    public function test_set_feature_flags_falseの値は無効として保存されること(): void
    {
        $project = $this->createProject();
        $flags = ShopSettingStore::getFeatureFlags($project->id);
        $flags['feature_coupons_enabled'] = false;
        $flags['feature_orders_enabled'] = true;

        ShopSettingStore::setFeatureFlags($flags, $project->id);

        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_coupons_enabled', $project->id));
        $this->assertTrue(ShopSettingStore::isFeatureEnabled('feature_orders_enabled', $project->id));
    }

    public function test_set_feature_flags_未指定キーは無効になること(): void
    {
        $project = $this->createProject();

        ShopSettingStore::setFeatureFlags([
            'feature_orders_enabled' => '1',
            'feature_members_enabled' => true,
        ], $project->id);

        $this->assertTrue(ShopSettingStore::isFeatureEnabled('feature_orders_enabled', $project->id));
        $this->assertTrue(ShopSettingStore::isFeatureEnabled('feature_members_enabled', $project->id));
        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_coupons_enabled', $project->id));
        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_gacha_enabled', $project->id));
    }

    public function test_ギャラリーの公開状態はホームメニューだけで決まること(): void
    {
        $project = $this->createProject();
        $flags = ShopSettingStore::getFeatureFlags($project->id);
        $flags['feature_gallery_enabled'] = '0';

        ShopSettingStore::setFeatureFlags($flags, $project->id);

        $this->assertTrue(ShopSettingStore::isFeatureEnabled('feature_gallery_enabled', $project->id));

        ShopSettingStore::setFrontendMenuItems([
            ['tab' => 'gallery', 'label' => 'ギャラリー', 'enabled' => false, 'order' => 10],
        ], $project->id);

        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_gallery_enabled', $project->id));
        $this->assertFalse(ShopSettingStore::getFeatureFlags($project->id)['feature_gallery_enabled']);

        $flags['feature_gallery_enabled'] = true;
        ShopSettingStore::setFeatureFlags($flags, $project->id);

        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_gallery_enabled', $project->id));
    }
}
