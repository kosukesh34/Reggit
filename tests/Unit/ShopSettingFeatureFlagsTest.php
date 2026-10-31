<?php

namespace Tests\Unit;

use App\Infrastructure\Persistence\Eloquent\Settings\FeatureFlagStore;
use App\Infrastructure\Persistence\Eloquent\Settings\LiffSettingStore;
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
        $flags = app(FeatureFlagStore::class)->getFeatureFlags($project->id);
        $flags['feature_coupons_enabled'] = false;
        $flags['feature_orders_enabled'] = true;

        app(FeatureFlagStore::class)->setFeatureFlags($flags, $project->id);

        $this->assertFalse(app(FeatureFlagStore::class)->isFeatureEnabled('feature_coupons_enabled', $project->id));
        $this->assertTrue(app(FeatureFlagStore::class)->isFeatureEnabled('feature_orders_enabled', $project->id));
    }

    public function test_set_feature_flags_未指定キーは無効になること(): void
    {
        $project = $this->createProject();

        app(FeatureFlagStore::class)->setFeatureFlags([
            'feature_orders_enabled' => '1',
            'feature_members_enabled' => true,
        ], $project->id);

        $this->assertTrue(app(FeatureFlagStore::class)->isFeatureEnabled('feature_orders_enabled', $project->id));
        $this->assertTrue(app(FeatureFlagStore::class)->isFeatureEnabled('feature_members_enabled', $project->id));
        $this->assertFalse(app(FeatureFlagStore::class)->isFeatureEnabled('feature_coupons_enabled', $project->id));
        $this->assertFalse(app(FeatureFlagStore::class)->isFeatureEnabled('feature_gacha_enabled', $project->id));
    }

    public function test_ギャラリーの公開状態はホームメニューだけで決まること(): void
    {
        $project = $this->createProject();
        $flags = app(FeatureFlagStore::class)->getFeatureFlags($project->id);
        $flags['feature_gallery_enabled'] = '0';

        app(FeatureFlagStore::class)->setFeatureFlags($flags, $project->id);

        $this->assertTrue(app(FeatureFlagStore::class)->isFeatureEnabled('feature_gallery_enabled', $project->id));

        app(LiffSettingStore::class)->setFrontendMenuItems([
            ['tab' => 'gallery', 'label' => 'ギャラリー', 'enabled' => false, 'order' => 10],
        ], $project->id);

        $this->assertFalse(app(FeatureFlagStore::class)->isFeatureEnabled('feature_gallery_enabled', $project->id));
        $this->assertFalse(app(FeatureFlagStore::class)->getFeatureFlags($project->id)['feature_gallery_enabled']);

        $flags['feature_gallery_enabled'] = true;
        app(FeatureFlagStore::class)->setFeatureFlags($flags, $project->id);

        $this->assertFalse(app(FeatureFlagStore::class)->isFeatureEnabled('feature_gallery_enabled', $project->id));
    }
}
