<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\ShopSetting;
use Illuminate\Http\Request;

class HomeController extends AdminBaseController
{
    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $defaults = ShopSetting::defaultFrontendMenuItems();
        $raw = ShopSetting::getValue('frontend_menu_items', null, $projectId);
        $byTab = collect(is_array($raw) ? $raw : [])->keyBy('tab');

        $tabs = collect($defaults)->map(function ($item) use ($byTab) {
            $saved = $byTab->get($item['tab']);
            return [
                'tab' => $item['tab'],
                'label' => $saved['label'] ?? $item['label'],
                'icon' => $saved['icon'] ?? $item['icon'],
                'order' => $saved['order'] ?? $item['order'],
                'enabled' => !isset($saved['enabled']) || $saved['enabled'] !== false,
            ];
        })->sortBy('order')->values()->all();

        return view('admin.settings.home', ['tabs' => $tabs]);
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $defaults = collect(ShopSetting::defaultFrontendMenuItems())->keyBy('tab');

        $items = [];
        $raw = $request->input('tabs', []);
        foreach ($raw as $index => $row) {
            $tab = $row['tab'] ?? null;
            if (\App\Util\StrHelper::isEmptyStringOrNull($tab) || $defaults->has($tab) === false) {
                continue;
            }
            $items[] = array_merge($defaults->get($tab), [
                'tab' => $tab,
                'label' => $row['label'] ?? $defaults->get($tab)['label'],
                'icon' => $row['icon'] ?? $defaults->get($tab)['icon'],
                'order' => (int) ($row['order'] ?? $index),
                'enabled' => isset($row['enabled']) && (bool) $row['enabled'],
            ]);
        }

        usort($items, fn ($a, $b) => $a['order'] <=> $b['order']);
        ShopSetting::setFrontendMenuItems($items, $projectId);

        return redirect()->route('admin.settings.home', $this->buildAdminRouteParams())
            ->with('success', 'ホーム設定を更新しました');
    }
}
