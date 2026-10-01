<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\HomeSettingsRepository;
use App\Support\LiffDesignerSanitizer;
use App\UseCases\Admin\Settings\Data\UpdateHomeImagesInput;
use App\UseCases\Admin\Settings\Data\UpdateHomeSettingsInput;
use App\Util\StrHelper;

class UpdateHomeSettingsUseCase
{
    public function __construct(
        private HomeSettingsRepository $shopSettingRepository,
        private UpdateHomeImagesUseCase $images,
    ) {}

    public function handle(UpdateHomeSettingsInput $input): void
    {
        $projectId = $input->projectId;
        $rawTabs = $input->tabs;
        $nativeNavigation = $input->navigation;
        $homeShowMemberCard = $input->showMemberCard;
        $homeMenuLayout = $input->menuLayout;
        $productDisplayLayout = $input->productLayout;
        $nativeChromeColors = $input->nativeColors;
        $navigationHeaderBackgroundColor = $input->headerBackground;
        $this->images->handle(new UpdateHomeImagesInput($projectId, $input->headerImage, $input->clearHeaderImage, $input->homeImage, $input->clearHomeImage, $input->homeImages, $input->removedHomeImages, $input->homeImagesPresent));
        if ($nativeChromeColors !== null) {
            $this->shopSettingRepository->setValue('native_chrome_colors', array_map('strtoupper', array_intersect_key($nativeChromeColors, array_flip(['header_icon', 'footer_background', 'footer_icon', 'footer_active_icon', 'footer_active_background', 'primary_button_background', 'primary_button_text', 'secondary_button_background', 'secondary_button_text']))), 'json', $projectId);
        }
        if ($homeShowMemberCard !== null) {
            $this->shopSettingRepository->setValue('home_show_member_card', $homeShowMemberCard, 'boolean', $projectId);
        }
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
            if ($isLink && array_key_exists('url', $row)) {
                $url = trim((string) ($row['url'] ?? ''));
                if ($url === '') {
                    $existingLinks->forget($tab);

                    continue;
                }
                $default['url'] = $url;
                $default['open'] = $row['open'] ?? $default['open'] ?? 'embed';
            }
            if ($isLink && empty($default['url'])) {
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
        if ($navigationHeaderBackgroundColor !== null) {
            $this->shopSettingRepository->setValue('navigation_header_background_color', strtoupper($navigationHeaderBackgroundColor), 'string', $projectId);
        }
        $this->shopSettingRepository->setValue('home_menu_layout', $homeMenuLayout, 'string', $projectId);
        $this->shopSettingRepository->setValue('product_display_layout', $productDisplayLayout, 'string', $projectId);
        if ($nativeNavigation !== null) {
            $allowedNavigationKeys = array_merge(
                array_values(array_filter(array_column($items, 'tab'), fn ($tab) => $tab !== 'designlab')),
                ['terms', 'privacy', 'profileEdit'],
            );
            $normalizedNavigation = [];
            foreach (['header' => 1, 'footer' => 5, 'hamburger' => null] as $placement => $limit) {
                $keys = array_values(array_unique(array_filter(
                    is_array($nativeNavigation[$placement] ?? null) ? $nativeNavigation[$placement] : [],
                    fn ($key) => is_string($key) && in_array($key, $allowedNavigationKeys, true),
                )));
                $normalizedNavigation[$placement] = $limit === null ? $keys : array_slice($keys, 0, $limit);
            }
            $this->shopSettingRepository->setValue('native_navigation_items', $normalizedNavigation, 'json', $projectId);
        }
    }

    /**
     * 送信されなかったWebページ設定は保存時に維持する。
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
