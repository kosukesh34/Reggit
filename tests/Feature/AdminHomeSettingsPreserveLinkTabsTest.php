<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\ShopSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        ShopSetting::setFrontendMenuItems([
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
            ])
            ->assertRedirect();

        $raw = ShopSetting::getValue('frontend_menu_items', null, $project->id);
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
}
