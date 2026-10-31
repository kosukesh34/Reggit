<?php

namespace Tests\Unit\Services\Admin;

use App\Infrastructure\Persistence\Eloquent\Settings\LiffSettingStore;
use App\Models\Project;
use App\Services\Admin\LiffSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiffSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private LiffSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Test', 'description' => 'Test', 'is_active' => true]
        );
        $this->service = app(LiffSettingsService::class);
    }

    public function test_get_menu_items_for_designer_デフォルトと保存値をマージしてorder順で返す(): void
    {
        app(LiffSettingStore::class)->setFrontendMenuItems([
            ['tab' => 'reservations', 'label' => 'ご予約', 'icon' => 'fa-calendar-check', 'order' => 0, 'enabled' => true],
            ['tab' => 'products', 'label' => 'お品書き', 'icon' => 'fa-utensils', 'order' => 2, 'enabled' => false],
        ], $this->project->id);

        $items = $this->service->getMenuItemsForDesigner($this->project->id);
        $byTab = collect($items)->keyBy('tab');

        $this->assertSame('ご予約', $byTab->get('reservations')['label']);
        $this->assertSame('ホーム', $byTab->get('home')['label']);
        $this->assertSame('お品書き', $byTab->get('products')['label']);
        $this->assertFalse($byTab->get('products')['enabled']);
        $this->assertSame(0, $byTab->get('reservations')['order']);
    }

    public function test_fill_menu_defaults_空のlabelとiconをデフォルトで補完し不正タブを除外する(): void
    {
        $filled = $this->service->fillMenuDefaults([
            ['tab' => 'home', 'label' => '', 'icon' => '', 'order' => 0, 'enabled' => true],
            ['tab' => 'invalid-tab', 'label' => 'x', 'icon' => 'fa-x', 'order' => 1, 'enabled' => true],
        ]);

        $this->assertCount(1, $filled);
        $this->assertSame('home', $filled[0]['tab']);
        $this->assertSame('ホーム', $filled[0]['label']);
        $this->assertSame('fa-house', $filled[0]['icon']);
    }

    public function test_set_tab_layouts_レイアウトを保存できる(): void
    {
        $layouts = ['home' => [['type' => 'text', 'title' => 'Hello']]];
        $this->service->setTabLayouts($layouts, $this->project->id);

        $this->assertSame($layouts['home'][0]['type'], $this->service->getTabLayouts($this->project->id)['home'][0]['type']);
    }
}
