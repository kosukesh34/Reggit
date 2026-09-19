<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Support\LiffDesignerSanitizer;
use App\Util\StrHelper;

class UpdateHomeSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /** @param array<int, array{tab?: string, label?: string, icon?: string, order?: int, enabled?: bool}> $rawTabs */
    public function handle(
        int $projectId,
        array $rawTabs,
        ?string $navigationHeaderImageUrl = null,
        string $navigationHeaderBackgroundColor = '#FFFFFF',
        ?string $homeImageUrl = null,
        string $homeMenuLayout = 'grid_3x2',
    ): void {
        $defaults = collect($this->shopSettingRepository->defaultFrontendMenuItems())->keyBy('tab');
        $existingLinks = collect($this->existingLinkMenuItems($projectId))->keyBy('tab');
        $items = [];
        foreach ($rawTabs as $index => $row) {
            $tab = $row['tab'] ?? null;
            $isLink = in_array($tab, LiffDesignerSanitizer::LINK_MENU_TABS, true);
            if (StrHelper::isEmptyStringOrNull($tab) || ($defaults->has($tab) === false && $isLink === false)) {
                continue;
            }
            $default = $isLink ? $existingLinks->get($tab, []) : $defaults->get($tab);
            if ($isLink && ! is_array($default)) {
                continue;
            }
            $items[] = array_merge($default, [
                'tab' => $tab,
                'label' => StrHelper::isEmptyStringOrNull($row['label'] ?? null) ? (string) ($default['label'] ?? 'リンク') : (string) $row['label'],
                'icon' => StrHelper::isEmptyStringOrNull($row['icon'] ?? null) ? (string) ($default['icon'] ?? 'fa-globe') : (string) $row['icon'],
                'order' => (int) ($row['order'] ?? $index),
                'enabled' => isset($row['enabled']) && filter_var($row['enabled'], FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $savedTabs = collect($items)->pluck('tab');
        $maxOrder = $items === [] ? -1 : max(array_column($items, 'order'));
        foreach ($existingLinks as $tab => $linkItem) {
            if ($savedTabs->contains($tab)) {
                continue;
            }
            $maxOrder++;
            $linkItem['order'] = $maxOrder;
            $items[] = $linkItem;
        }

        usort($items, fn ($a, $b) => $a['order'] <=> $b['order']);
        $this->shopSettingRepository->setFrontendMenuItems($items, $projectId);
        if ($navigationHeaderImageUrl !== null) {
            $this->shopSettingRepository->setValue('navigation_header_image_url', $navigationHeaderImageUrl, 'string', $projectId);
        }
        $this->shopSettingRepository->setValue('navigation_header_background_color', strtoupper($navigationHeaderBackgroundColor), 'string', $projectId);
        if ($homeImageUrl !== null) {
            $this->shopSettingRepository->setValue('home_image_url', $homeImageUrl, 'string', $projectId);
        }
        $this->shopSettingRepository->setValue('home_menu_layout', $homeMenuLayout, 'string', $projectId);
    }

    /**
     * LIFFデザイナーで追加した外部リンクタブはホーム設定では扱わないため、保存時に維持する。
     *
     * @return list<array<string, mixed>>
     */
    private function existingLinkMenuItems(int $projectId): array
    {
        $raw = $this->shopSettingRepository->getValue('frontend_menu_items', null, $projectId);
        if (! is_array($raw)) {
            return [];
        }

        $links = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $tab = (string) ($item['tab'] ?? '');
            if (! in_array($tab, LiffDesignerSanitizer::LINK_MENU_TABS, true)) {
                continue;
            }
            $links[] = $item;
        }

        return $links;
    }
}
