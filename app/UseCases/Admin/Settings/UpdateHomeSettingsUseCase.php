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
    public function handle(int $projectId, array $rawTabs): void
    {
        $defaults = collect($this->shopSettingRepository->defaultFrontendMenuItems())->keyBy('tab');
        $items = [];
        foreach ($rawTabs as $index => $row) {
            $tab = $row['tab'] ?? null;
            if (StrHelper::isEmptyStringOrNull($tab) || $defaults->has($tab) === false) {
                continue;
            }
            $default = $defaults->get($tab);
            $items[] = array_merge($default, [
                'tab' => $tab,
                'label' => StrHelper::isEmptyStringOrNull($row['label'] ?? null) ? $default['label'] : (string) $row['label'],
                'icon' => StrHelper::isEmptyStringOrNull($row['icon'] ?? null) ? $default['icon'] : (string) $row['icon'],
                'order' => (int) ($row['order'] ?? $index),
                'enabled' => isset($row['enabled']) && (bool) $row['enabled'],
            ]);
        }

        $maxOrder = $items === [] ? -1 : max(array_column($items, 'order'));
        foreach ($this->existingLinkMenuItems($projectId) as $linkItem) {
            $maxOrder++;
            $linkItem['order'] = $maxOrder;
            $items[] = $linkItem;
        }

        usort($items, fn ($a, $b) => $a['order'] <=> $b['order']);
        $this->shopSettingRepository->setFrontendMenuItems($items, $projectId);
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
