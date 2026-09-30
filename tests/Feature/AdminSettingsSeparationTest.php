<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminSettingsSeparationTest extends TestCase
{
    use RefreshDatabase;

    private function login(): Project
    {
        $project = Project::factory()->create();
        $this->actingAs(AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]), 'admin');

        return $project;
    }

    public function test_rank_rules_and_card_design_are_on_separate_pages(): void
    {
        $project = $this->login();
        $this->get('/admin/'.$project->project_key.'/settings/ranks')->assertOk()
            ->assertSee('ランク・ポイント・スタンプ')
            ->assertSee('name="rank_basis"', false)
            ->assertDontSee('name="member_card_design"', false);
        $this->get('/admin/'.$project->project_key.'/settings/app-design/member-card')->assertOk()
            ->assertSee('アプリデザイン')
            ->assertSee('name="member_card_design"', false)
            ->assertDontSee('name="rank_basis"', false);
    }

    public function test_card_design_save_preserves_rank_rules_and_points(): void
    {
        $project = $this->login();
        $ranks = [['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 1000, 'is_active' => true, 'order' => 1]];
        ShopSettingStore::setMemberRankSettings($ranks, $project->id);
        $existingRanks = ShopSettingStore::getMemberRankSettings($project->id);
        ShopSettingStore::setValue('point_earn_rate', 80, 'integer', $project->id);
        ShopSettingStore::setValue('use_points', false, 'boolean', $project->id);

        $this->post('/admin/'.$project->project_key.'/settings/app-design/member-card', [
            'member_card_design' => 'compact',
            'member_card_code_type' => 'qrcode',
            'show_points_display' => '0',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('compact', ShopSettingStore::getMemberCardDesign($project->id));
        $this->assertSame($existingRanks, ShopSettingStore::getMemberRankSettings($project->id));
        $this->assertSame(80, ShopSettingStore::getPointEarnRate($project->id));
        $this->assertFalse(ShopSettingStore::getUsePoints($project->id));
    }

    public function test_rank_rule_save_preserves_card_display_options(): void
    {
        $project = $this->login();
        ShopSettingStore::setValue('show_points_display', false, 'boolean', $project->id);
        ShopSettingStore::setValue('show_rank_progress', false, 'boolean', $project->id);
        ShopSettingStore::setValue('show_member_card_for_guest', false, 'boolean', $project->id);
        $this->post('/admin/'.$project->project_key.'/settings/ranks', [
            'rank_basis' => 'points',
            'rank_aggregation_type' => 'total',
            'point_earn_rate' => 100,
            'points_valid_days' => 365,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse(ShopSettingStore::getShowPointsDisplay($project->id));
        $this->assertFalse(ShopSettingStore::getShowRankProgress($project->id));
        $this->assertFalse(ShopSettingStore::getShowMemberCardForGuest($project->id));
    }

    public function test_branding_and_admin_colors_preserve_features_and_each_other(): void
    {
        $project = $this->login();
        ShopSettingStore::setFeatureFlags(['feature_orders_enabled' => true], $project->id);
        $features = ShopSettingStore::getFeatureFlags($project->id);
        ShopSettingStore::setValue('admin_sidebar_color', '#123456', 'string', $project->id);
        $this->post('/admin/'.$project->project_key.'/settings/app-design/branding', [
            'frontend_header_text' => '新しい店舗名',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($features, ShopSettingStore::getFeatureFlags($project->id));
        $this->assertSame('#123456', ShopSettingStore::getValue('admin_sidebar_color', '', $project->id));

        $this->post('/admin/'.$project->project_key.'/settings/admin-design', [
            'admin_sidebar_color' => '#654321',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('新しい店舗名', ShopSettingStore::getFrontendHeaderText($project->id));
        $this->assertSame($features, ShopSettingStore::getFeatureFlags($project->id));
    }

    public function test_features_page_has_no_branding_or_admin_color_fields(): void
    {
        $project = $this->login();
        ShopSettingStore::setValue('frontend_header_text', '店舗名', 'string', $project->id);
        $this->get('/admin/'.$project->project_key.'/settings/features')->assertOk()
            ->assertSee('利用機能')
            ->assertDontSee('name="frontend_header_text"', false)
            ->assertDontSee('name="admin_sidebar_color"', false);
        $this->post('/admin/'.$project->project_key.'/settings/features', [])->assertRedirect();
        $this->assertSame('店舗名', ShopSettingStore::getFrontendHeaderText($project->id));
        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_orders_enabled', $project->id));
    }

    public function test_member_code_is_in_profile_and_rank_save_cannot_change_it_or_features(): void
    {
        $project = $this->login();
        $base = '/admin/'.$project->project_key.'/settings';
        $this->get($base.'/profile')->assertOk()->assertSee('name="member_code_prefix"', false);
        $this->post($base.'/member-code', [
            'member_code_type' => 'random', 'member_code_format' => 'alphanumeric',
            'member_code_length' => 8, 'member_code_prefix' => 'ABC',
        ])->assertRedirect()->assertSessionHasNoErrors();
        ShopSettingStore::setValue('use_points', true, 'boolean', $project->id);
        $this->post($base.'/ranks', [
            'rank_basis' => 'points', 'rank_aggregation_type' => 'total', 'point_earn_rate' => 100, 'points_valid_days' => 365,
            'member_code_prefix' => 'WRONG', 'use_points' => 0, 'member_card_design' => 'ticket',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('ABC', ShopSettingStore::getMemberCodePrefix($project->id));
        $this->assertTrue(ShopSettingStore::getUsePoints($project->id));
        $this->assertSame('default', ShopSettingStore::getMemberCardDesign($project->id));
    }

    public function test_announcement_defaults_save_preserves_feature_and_saves_unchecked_pinned(): void
    {
        $project = $this->login();
        ShopSettingStore::setFeatureFlags(['feature_announcements_enabled' => true], $project->id);
        ShopSettingStore::setValue('announcement_default_pinned', true, 'boolean', $project->id);
        $this->post('/admin/'.$project->project_key.'/settings/announcements', [
            'announcement_default_pinned' => 0, 'announcement_default_expiry_days' => 7,
            'feature_announcements_enabled' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(ShopSettingStore::isFeatureEnabled('feature_announcements_enabled', $project->id));
        $this->assertFalse((bool) ShopSettingStore::getValue('announcement_default_pinned', true, $project->id));
        $this->assertSame(7, (int) ShopSettingStore::getValue('announcement_default_expiry_days', 0, $project->id));
    }

    public function test_color_save_preserves_navigation_and_unsupplied_colors(): void
    {
        $project = $this->login();
        ShopSettingStore::setValue('native_chrome_colors', ['header_icon' => '#123456', 'footer_icon' => '#234567'], 'json', $project->id);
        ShopSettingStore::setValue('home_menu_layout', 'list', 'string', $project->id);
        $this->post('/admin/'.$project->project_key.'/settings/advanced', [
            'native_chrome_colors' => ['header_icon' => '#aabbcc'],
            'home_menu_layout' => 'grid',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $colors = ShopSettingStore::getValue('native_chrome_colors', [], $project->id);
        $this->assertSame('#AABBCC', $colors['header_icon']);
        $this->assertSame('#234567', $colors['footer_icon']);
        $this->assertSame('list', ShopSettingStore::getValue('home_menu_layout', '', $project->id));
    }

    public function test_gallery_over_limit_does_not_partially_save_other_images_or_name(): void
    {
        $project = $this->login();
        $urls = array_fill(0, 10, 'https://example.com/image.jpg');
        ShopSettingStore::setValue('home_image_urls', $urls, 'json', $project->id);
        ShopSettingStore::setValue('frontend_header_text', 'Before', 'string', $project->id);
        $this->post('/admin/'.$project->project_key.'/settings/app-design/branding', [
            'home_images_present' => 1, 'frontend_header_text' => 'After',
            'home_images' => [UploadedFile::fake()->image('extra.png')],
        ])->assertSessionHasErrors('home_images');
        $this->assertSame($urls, ShopSettingStore::getValue('home_image_urls', [], $project->id));
        $this->assertSame('Before', ShopSettingStore::getFrontendHeaderText($project->id));
    }
}
