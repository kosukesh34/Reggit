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

        ShopSettingStore::setFeatureFlags([
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

        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_coupons_enabled', $project->id));
        $this->assertTrue(ShopSettingStore::isFeatureEnabled('feature_orders_enabled', $project->id));
        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_reservations_enabled', $project->id));
        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_gallery_enabled', $project->id));
        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_announcements_enabled', $project->id));
        $this->assertTrue(ShopSettingStore::isFeatureEnabled('feature_stamps_enabled', $project->id));
    }

    public function test_クーポン設定画面にドロップゾーンがあること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.coupons', ['project_key' => $project->project_key]))
            ->assertStatus(200)
            ->assertSee('name="coupon_default_image"', false)
            ->assertSee('ドラッグ＆ドロップ')
            ->assertDontSee('name="coupon_default_image_url"', false);
    }

    public function test_クーポンデフォルト画像をアップロードできること(): void
    {
        Storage::fake('public');
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $file = UploadedFile::fake()->image('coupon-default.png', 80, 80);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.coupons.update', ['project_key' => $project->project_key]), [
                'feature_coupons_enabled' => '1',
                'coupon_default_image' => $file,
            ])
            ->assertRedirect();

        $savedUrl = ShopSettingStore::getCouponDefaultImageUrl($project->id);
        $this->assertNotNull($savedUrl);
        $this->assertStringContainsString('storage/'.PublicImageStorageService::DIR_COUPON_DEFAULT.'/'.$project->id, (string) $savedUrl);
    }

    public function test_クーポンデフォルト画像をクリアできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        ShopSettingStore::setValue('coupon_default_image_url', 'https://example.com/old-coupon.png', 'string', $project->id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.coupons.update', ['project_key' => $project->project_key]), [
                'feature_coupons_enabled' => '1',
                'coupon_default_image_clear' => '1',
            ])
            ->assertRedirect();

        $this->assertSame('', (string) (ShopSettingStore::getCouponDefaultImageUrl($project->id) ?? ''));
    }
}
