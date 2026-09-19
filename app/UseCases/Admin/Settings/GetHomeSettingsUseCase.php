<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Support\LiffDesignerSanitizer;

class GetHomeSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): array
    {
        $defaults = $this->shopSettingRepository->defaultFrontendMenuItems();
        $raw = $this->shopSettingRepository->getValue('frontend_menu_items', null, $projectId);
        $byTab = collect(is_array($raw) ? $raw : [])->keyBy('tab');

        $tabs = collect($defaults)->map(function ($item) use ($byTab) {
            $saved = $byTab->get($item['tab']);

            return [
                'tab' => $item['tab'],
                'label' => $saved['label'] ?? $item['label'],
                'icon' => $saved['icon'] ?? $item['icon'],
                'order' => $saved['order'] ?? $item['order'],
                'enabled' => $this->normalizeEnabled($saved['enabled'] ?? null),
            ];
        });
        $linkTabs = collect(is_array($raw) ? $raw : [])->filter(
            fn ($item) => is_array($item) && in_array($item['tab'] ?? '', LiffDesignerSanitizer::LINK_MENU_TABS, true)
        )->map(fn ($item) => [
            'tab' => (string) $item['tab'],
            'label' => (string) ($item['label'] ?? 'リンク'),
            'icon' => (string) ($item['icon'] ?? 'fa-globe'),
            'order' => (int) ($item['order'] ?? 999),
            'enabled' => $this->normalizeEnabled($item['enabled'] ?? null),
            'url' => (string) ($item['url'] ?? ''),
        ]);
        $tabs = $tabs->concat($linkTabs)->sortBy('order')->values()->all();

        $availableIcons = collect($defaults)->pluck('icon')->push('fa-globe')->unique()->values()->all();

        return [
            'tabs' => $tabs,
            'availableIcons' => $availableIcons,
            'navigationHeaderImageUrl' => (string) $this->shopSettingRepository->getValue('navigation_header_image_url', '', $projectId),
            'navigationHeaderBackgroundColor' => (string) $this->shopSettingRepository->getValue('navigation_header_background_color', '#FFFFFF', $projectId),
            'homeImageUrl' => (string) $this->shopSettingRepository->getValue('home_image_url', '', $projectId),
            'homeMenuLayout' => (string) $this->shopSettingRepository->getValue('home_menu_layout', 'grid_3x2', $projectId),
        ];
    }

    private function normalizeEnabled(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
