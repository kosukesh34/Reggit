<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\GalleryItem;
use App\Models\Project;
use App\Services\AppConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeContentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_settings_are_saved_and_shared_in_order_without_other_tenants_images(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $last = GalleryItem::create(['project_id' => $project->id, 'title' => 'Last', 'image_url' => '/last.jpg', 'sort_order' => 20, 'is_active' => true]);
        $first = GalleryItem::create(['project_id' => $project->id, 'title' => 'First', 'description' => 'Details', 'image_url' => '/first.jpg', 'sort_order' => 1, 'is_active' => true]);
        $foreign = GalleryItem::create(['project_id' => $other->id, 'title' => 'Other tenant', 'image_url' => '/foreign.jpg', 'sort_order' => 0, 'is_active' => true]);
        GalleryItem::create(['project_id' => $project->id, 'title' => 'Hidden', 'image_url' => '/hidden.jpg', 'sort_order' => 0, 'is_active' => false]);
        $payload = [
            'home_menu_layout' => 'grid_3x2', 'product_display_layout' => 'list_with_image',
            'home_content' => ['layout' => 'buttons_gallery', 'image_limit' => 1, 'actions' => [
                $first->id => ['mode' => 'webview', 'url' => 'https://example.com/campaign'],
                $last->id => ['mode' => 'external', 'url' => 'https://example.com'],
                $foreign->id => ['mode' => 'detail'],
            ]],
        ];
        $endpoint = route('admin.settings.home.update', ['project_key' => $project->project_key]);
        $this->actingAs($admin, 'admin')->post($endpoint, $payload)->assertSessionHasNoErrors();
        $saved = ShopSettingStore::getValue('home_content', [], $project->id);
        $this->assertArrayNotHasKey($foreign->id, $saved['actions']);
        $home = app(AppConfigService::class)->buildForProjectKey($project->project_key)['home'];
        $this->assertSame('buttons_gallery', $home['layout']);
        $this->assertCount(1, $home['gallery_items']);
        $this->assertSame($first->id, $home['gallery_items'][0]['id']);
        $this->assertSame('webview', $home['gallery_items'][0]['mode']);
        $this->assertSame('https://example.com/campaign', $home['gallery_items'][0]['url']);
        $payload['home_content']['image_limit'] = '';
        $payload['home_gallery'] = [$first->id => ['title' => 'Updated', 'description' => 'New details', 'sort_order' => 2], $foreign->id => ['title' => 'Must not update', 'sort_order' => 3]];
        $this->post($endpoint, $payload)->assertSessionHasNoErrors();
        $home = app(AppConfigService::class)->buildForProjectKey($project->project_key)['home'];
        $this->assertSame([$first->id, $last->id], array_column($home['gallery_items'], 'id'));
        $this->assertSame('external', $home['gallery_items'][1]['mode']);
        $this->assertSame('Updated', $home['gallery_items'][0]['title']);
        $this->assertSame('New details', $home['gallery_items'][0]['description']);
        $this->assertSame('Other tenant', $foreign->fresh()->title);
        $this->get(route('admin.settings.home', ['project_key' => $project->project_key]))->assertOk()->assertSee('home-live-preview', false)->assertSee('画像ごとのタップ動作');
        $this->get(route('admin.settings.home', ['project_key' => $project->project_key]))->assertSee('src="/liff/'.$project->project_key.'?', false);
    }

    public function test_invalid_or_missing_link_targets_and_invalid_limits_are_rejected(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $endpoint = route('admin.settings.home.update', ['project_key' => $project->project_key]);
        $payload = ['home_menu_layout' => 'grid_3x2', 'product_display_layout' => 'list_with_image', 'home_content' => ['layout' => 'gallery', 'image_limit' => 0, 'actions' => [1 => ['mode' => 'webview', 'url' => 'javascript:alert(1)']]]];
        $this->actingAs($admin, 'admin')->post($endpoint, $payload)->assertSessionHasErrors(['home_content.image_limit', 'home_content.actions.1.url']);
        $payload['home_content']['image_limit'] = 2;
        $payload['home_content']['actions'][1]['url'] = '';
        $this->post($endpoint, $payload)->assertSessionHasErrors('home_content.actions.1.url');
        $payload['home_content']['actions'][1]['mode'] = 'detail';
        $this->post($endpoint, $payload)->assertSessionHasNoErrors();
    }

    public function test_only_authorized_admin_preview_skips_line_login_and_loading_screen(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $url = '/liff/'.$project->project_key.'?designer_live_preview=1&designer_embed=1&home_form_preview=1';
        $this->get($url)->assertOk()->assertSee('name="line-login-required"', false);
        $this->actingAs($admin, 'admin')->get($url)->assertOk()->assertDontSee('name="line-login-required"', false)->assertDontSee('id="liff-loading"', false)->assertSee('window.__IS_DESIGNER_EMBED_PREVIEW__ = true', false);
        $this->get('/liff/'.$project->project_key)->assertOk()->assertSee('name="line-login-required"', false);
        $other = Project::factory()->create();
        $this->get('/liff/'.$other->project_key.'?designer_live_preview=1&designer_embed=1')->assertOk()->assertSee('name="line-login-required"', false);
    }

    public function test_member_page_styles_save_without_overwriting_other_design_settings(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        ShopSettingStore::setValue('liff_tab_settings', ['_global' => ['theme_preset' => 'test'], 'member' => ['title' => 'Card']], 'json', $project->id);
        $endpoint = route('admin.settings.member-card.update', ['project_key' => $project->project_key]);
        foreach (['minimal', 'ticket', 'wallet'] as $style) {
            $this->actingAs($admin, 'admin')->post($endpoint, ['member_page_style' => $style])->assertSessionHasNoErrors();
            $config = app(AppConfigService::class)->buildForProjectKey($project->project_key);
            $this->assertSame($style, $config['member_screen']['style']);
            $this->assertSame('test', ShopSettingStore::getValue('liff_tab_settings', [], $project->id)['_global']['theme_preset']);
        }
        $this->post($endpoint, ['member_page_style' => 'invalid'])->assertSessionHasErrors('member_page_style');
        $this->get(route('admin.settings.member-card', ['project_key' => $project->project_key]))->assertOk()->assertSee('member-page-preview', false)->assertSee('会員証ページのスタイル');
    }

    public function test_existing_layout_and_settings_are_preserved_when_new_fields_are_absent(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $this->assertSame('legacy', app(AppConfigService::class)->buildForProjectKey($project->project_key)['home']['layout']);
        ShopSettingStore::setValue('home_content', ['layout' => 'member_news', 'image_limit' => null], 'json', $project->id);
        $this->actingAs($admin, 'admin')->post(route('admin.settings.home.update', ['project_key' => $project->project_key]), ['home_menu_layout' => 'list', 'product_display_layout' => 'name_only'])->assertSessionHasNoErrors();
        $home = app(AppConfigService::class)->buildForProjectKey($project->project_key)['home'];
        $this->assertSame('member_news', $home['layout']);
        $this->assertSame([], $home['gallery_items']);
    }
}
