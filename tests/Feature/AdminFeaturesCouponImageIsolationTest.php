<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeaturesCouponImageIsolationTest extends TestCase
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

    public function test_features_settings_保存でクーポンデフォルト画像を消さないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $imageUrl = 'https://example.com/coupon-default.png';

        ShopSettingStore::setValue('coupon_default_image_url', $imageUrl, 'string', $project->id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.features.update', ['project_key' => $project->project_key]), [
                'features' => [
                    'feature_orders_enabled' => '1',
                    'feature_members_enabled' => '1',
                ],
                'stamp_default_image_url' => '',
                'frontend_logo_url' => '',
                'frontend_header_text' => '',
            ])
            ->assertRedirect();

        $this->assertSame($imageUrl, ShopSettingStore::getCouponDefaultImageUrl($project->id));
    }

    public function test_features_settings_画面にクーポンデフォルト画像入力がないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.features', ['project_key' => $project->project_key]))
            ->assertStatus(200)
            ->assertDontSee('name="coupon_default_image_url"', false)
            ->assertSee('クーポン設定');
    }
}
