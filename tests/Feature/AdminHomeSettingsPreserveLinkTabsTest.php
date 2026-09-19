<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use App\UseCases\Admin\Settings\GetHomeSettingsUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminHomeSettingsPreserveLinkTabsTest extends TestCase
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

    public function test_home_settings_保存でリンクタブを消さないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        ShopSettingStore::setFrontendMenuItems([
            ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-home', 'order' => 0, 'enabled' => true],
            ['tab' => 'products', 'label' => 'オーダー', 'icon' => 'fa-utensils', 'order' => 1, 'enabled' => true],
            [
                'tab' => 'link_1',
                'label' => '公式サイト',
                'icon' => 'fa-globe',
                'order' => 2,
                'enabled' => true,
                'url' => 'https://example.com',
                'open' => 'embed',
            ],
        ], $project->id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.home.update', ['project_key' => $project->project_key]), [
                'tabs' => [
                    ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-home', 'order' => 0, 'enabled' => '1'],
                    ['tab' => 'products', 'label' => 'オーダー', 'icon' => 'fa-utensils', 'order' => 1, 'enabled' => '1'],
                    ['tab' => 'member', 'label' => '会員証', 'icon' => 'fa-id-card', 'order' => 2, 'enabled' => '1'],
                ],
                'home_menu_layout' => 'grid_3x2',
            ])
            ->assertRedirect();

        $raw = ShopSettingStore::getValue('frontend_menu_items', null, $project->id);
        $this->assertIsArray($raw);
        $tabs = array_column($raw, 'tab');
        $this->assertContains('home', $tabs);
        $this->assertContains('products', $tabs);
        $this->assertContains('member', $tabs);
        $this->assertContains('link_1', $tabs);

        $link = collect($raw)->firstWhere('tab', 'link_1');
        $this->assertSame('公式サイト', $link['label']);
        $this->assertSame('https://example.com', $link['url']);
    }

    public function test_home_settings_ナビゲーションバー画像をアップロードできること(): void
    {
        Storage::fake('public');
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.home.update', ['project_key' => $project->project_key]), [
                'navigation_header_image' => UploadedFile::fake()->image('header.jpg', 600, 120),
                'home_menu_layout' => 'grid_3x2',
            ])
            ->assertRedirect();

        $url = (string) ShopSettingStore::getValue('navigation_header_image_url', '', $project->id);
        $this->assertStringContainsString('/storage/navigation_headers/'.$project->id.'/', $url);
        Storage::disk('public')->assertExists(parse_url($url, PHP_URL_PATH) !== null
            ? ltrim(str_replace('/storage/', '', (string) parse_url($url, PHP_URL_PATH)), '/')
            : 'missing');
        $this->assertSame('grid_3x2', ShopSettingStore::getValue('home_menu_layout', '', $project->id));
    }

    public function test_home_menu_非表示設定を保存して再表示しても維持すること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.home.update', ['project_key' => $project->project_key]), [
                'tabs' => [
                    ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-home', 'order' => 0, 'enabled' => '1'],
                    ['tab' => 'products', 'label' => 'オーダー', 'icon' => 'fa-utensils', 'order' => 1, 'enabled' => '0'],
                ],
                'home_menu_layout' => 'grid_3x2',
            ])
            ->assertRedirect();

        $saved = collect(ShopSettingStore::getValue('frontend_menu_items', [], $project->id))->firstWhere('tab', 'products');
        $this->assertFalse($saved['enabled']);

        $view = app(GetHomeSettingsUseCase::class)->handle((int) $project->id);
        $product = collect($view['tabs'])->firstWhere('tab', 'products');
        $this->assertFalse($product['enabled']);
    }
}
