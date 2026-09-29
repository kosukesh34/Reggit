<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNativeDesignSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_design_settings_save_card_visibility_and_profile_editor_navigation(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default', 'is_active' => true]);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $this->actingAs($admin, 'admin')->post(route('admin.settings.home.update', ['project_key' => $project->project_key]), [
            'home_show_member_card' => '0',
            'home_menu_layout' => 'list',
            'product_display_layout' => 'list_with_image',
            'native_navigation_present' => '1',
            'native_navigation' => ['header' => ['profileEdit'], 'footer' => ['home', 'member']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertFalse((bool) ShopSettingStore::getValue('home_show_member_card', true, $project->id));
        $this->assertSame(['profileEdit'], ShopSettingStore::getValue('native_navigation_items', [], $project->id)['header']);
        $this->getJson('/api/v1/app-config?project_key='.$project->project_key)->assertOk()->assertJsonPath('home.show_member_card', false)->assertJsonPath('navigation.header.0.key', 'profileEdit');
        $this->get(route('admin.settings.home', ['project_key' => $project->project_key]))->assertOk()->assertSee('プロフィール編集')->assertSee('ホームに会員証を表示する');
    }

    public function test_navigation_colors_are_saved_and_returned_to_the_app(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default', 'is_active' => true]);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $colors = ['header_icon' => '#ffffff', 'footer_background' => '#173f35', 'footer_active_background' => '#ffffff', 'primary_button_background' => '#123456', 'primary_button_text' => '#ffffff', 'secondary_button_background' => '#eeeeee', 'secondary_button_text' => '#333333'];
        $this->actingAs($admin, 'admin')->post(route('admin.settings.home.update', ['project_key' => $project->project_key]), [
            'home_menu_layout' => 'list',
            'product_display_layout' => 'list_with_image',
            'native_chrome_colors' => $colors,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->getJson('/api/v1/app-config?project_key=default')->assertOk()
            ->assertJsonPath('native_chrome_colors.header_icon', '#FFFFFF')
            ->assertJsonPath('native_chrome_colors.footer_background', '#173F35')
            ->assertJsonPath('native_chrome_colors.primary_button_background', '#123456')
            ->assertJsonPath('native_chrome_colors.secondary_button_text', '#333333');
        $settingsPage = $this->get(route('admin.settings.home', ['project_key' => $project->project_key]))->assertOk();
        $settingsPage->assertDontSee('native_chrome_colors[header_button_background]', false)
            ->assertDontSee('native_chrome_colors[footer_button_background]', false);
        $this->post(route('admin.settings.home.update', ['project_key' => $project->project_key]), [
            'home_menu_layout' => 'grid_3x2', 'product_display_layout' => 'list_with_image',
        ])->assertSessionHasNoErrors();
        $this->assertSame('#FFFFFF', ShopSettingStore::getValue('native_chrome_colors', [], $project->id)['header_icon']);
    }

    public function test_invalid_navigation_colors_are_rejected(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default', 'is_active' => true]);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $this->actingAs($admin, 'admin')->post(route('admin.settings.home.update', ['project_key' => $project->project_key]), [
            'home_menu_layout' => 'list', 'product_display_layout' => 'list_with_image',
            'native_chrome_colors' => ['header_icon' => 'red'],
        ])->assertSessionHasErrors('native_chrome_colors.header_icon');
    }

    public function test_saved_home_icons_are_replaced_with_matching_navigation_icons(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'Default', 'is_active' => true]);
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        ShopSettingStore::setValue('frontend_menu_items', [
            ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-house', 'order' => 0, 'enabled' => true],
            ['tab' => 'products', 'label' => 'モバイル注文', 'icon' => 'fa-house', 'order' => 1, 'enabled' => true],
            ['tab' => 'member', 'label' => '会員証', 'icon' => 'fa-house', 'order' => 2, 'enabled' => true],
        ], 'json', $project->id);
        ShopSettingStore::setValue('native_navigation_items', [
            'header' => [], 'footer' => ['home', 'products', 'member'], 'hamburger' => ['home', 'products', 'member'],
        ], 'json', $project->id);

        $response = $this->getJson('/api/v1/app-config?project_key=default')->assertOk();
        $this->assertSame(['fa-house', 'fa-utensils', 'fa-id-card'], array_column($response->json('navigation.footer'), 'icon'));
        $this->actingAs($admin, 'admin')->get(route('admin.settings.home', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('value="fa-utensils" selected', false)
            ->assertSee('value="fa-id-card" selected', false);
    }
}
