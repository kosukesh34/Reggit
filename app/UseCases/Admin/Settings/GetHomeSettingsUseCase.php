<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\HomeSettingsRepository;
use App\Infrastructure\Persistence\Eloquent\Settings\LiffSettingStore;
use App\Models\Announcement;
use App\Models\GalleryItem;
use App\Models\Project;
use App\Support\HomeImageUrls;
use App\Support\LiffDesignerSanitizer;

class GetHomeSettingsUseCase
{
    public function __construct(
        private HomeSettingsRepository $shopSettingRepository,
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
                'icon' => LiffSettingStore::normalizeFrontendMenuIcon($item['tab'], (string) ($saved['icon'] ?? $item['icon'])),
                'order' => $saved['order'] ?? $item['order'],
                'enabled' => $this->normalizeEnabled($saved['enabled'] ?? null),
            ];
        });
        $linkTabs = collect(LiffDesignerSanitizer::LINK_MENU_TABS)->map(function ($tab, $index) use ($byTab) {
            $item = $byTab->get($tab, []);

            return [
                'tab' => $tab,
                'label' => (string) ($item['label'] ?? 'Webページ'.($index + 1)),
                'icon' => (string) ($item['icon'] ?? 'fa-globe'),
                'order' => (int) ($item['order'] ?? 999 + $index),
                'enabled' => isset($item['url']) && $this->normalizeEnabled($item['enabled'] ?? null),
                'url' => (string) ($item['url'] ?? ''),
                'open' => ($item['open'] ?? 'embed') === 'external' ? 'external' : 'embed',
            ];
        });
        $tabs = $tabs->concat($linkTabs)->sortBy('order')->values()->all();

        $availableIcons = collect($defaults)->pluck('icon')->merge(collect($tabs)->pluck('icon'))->push('fa-globe')->unique()->values()->all();
        $savedNavigation = $this->shopSettingRepository->getValue('native_navigation_items', null, $projectId);
        $nativeTabs = collect($tabs)->reject(fn ($tab) => $tab['tab'] === 'designlab');
        $defaultFooter = $nativeTabs->where('enabled', true)->pluck('tab')->take(5)->values()->all();
        $defaultHamburger = $nativeTabs->where('enabled', true)->pluck('tab')->values()->all();
        $nativeNavigation = is_array($savedNavigation) ? $savedNavigation : [
            'header' => [],
            'footer' => $defaultFooter,
            'hamburger' => array_merge($defaultHamburger, ['terms', 'privacy']),
        ];
        $projectKey = (string) (Project::query()->whereKey($projectId)->value('project_key') ?? '');
        $termsUrl = $projectKey !== '' ? route('legal.terms', ['project_key' => $projectKey]) : '';
        $privacyUrl = $projectKey !== '' ? route('legal.privacy', ['project_key' => $projectKey]) : '';
        $nativeNavigationItems = array_merge(
            $nativeTabs->map(fn ($tab) => [
                'key' => (string) $tab['tab'],
                'label' => (string) $tab['label'],
            ])->all(),
            [
                ['key' => 'profileEdit', 'label' => 'プロフィール編集'],
                ['key' => 'terms', 'label' => '利用規約', 'url' => $termsUrl],
                ['key' => 'privacy', 'label' => 'プライバシーポリシー', 'url' => $privacyUrl],
            ],
        );
        $legalKeys = ['terms', 'privacy'];

        return [
            'nativeChromeColors' => $this->shopSettingRepository->getValue('native_chrome_colors', [], $projectId),
            'homePreviewProjectName' => (string) (Project::whereKey($projectId)->value('name') ?? 'Reggit'),
            'homeContent' => $this->shopSettingRepository->getValue('home_content', ['layout' => 'legacy'], $projectId),
            'homeGalleryItems' => GalleryItem::where('project_id', $projectId)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'homePreviewNews' => Announcement::where('project_id', $projectId)->whereNotNull('published_at')->where('published_at', '<=', now())->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->orderByDesc('is_pinned')->orderByDesc('published_at')->limit(3)->get(['id', 'title']),
            'homeShowMemberCard' => (bool) $this->shopSettingRepository->getValue('home_show_member_card', true, $projectId),
            'tabs' => $tabs,
            'availableIcons' => $availableIcons,
            'navigationHeaderImageUrl' => (string) $this->shopSettingRepository->getValue('navigation_header_image_url', '', $projectId),
            'navigationHeaderBackgroundColor' => (string) $this->shopSettingRepository->getValue('navigation_header_background_color', '#FFFFFF', $projectId),
            'homeImageUrl' => (string) $this->shopSettingRepository->getValue('home_image_url', '', $projectId),
            'homeImageUrls' => HomeImageUrls::forProject($this->shopSettingRepository, $projectId),
            'homeMenuLayout' => (string) $this->shopSettingRepository->getValue('home_menu_layout', 'grid_3x2', $projectId),
            'productDisplayLayout' => (string) $this->shopSettingRepository->getValue('product_display_layout', 'list_with_image', $projectId),
            'nativeNavigation' => $nativeNavigation,
            'nativeNavigationItems' => $nativeNavigationItems,
            'headerNavChoices' => $this->orderedNavigationChoices(
                $nativeNavigationItems,
                is_array($nativeNavigation['header'] ?? null) ? $nativeNavigation['header'] : [],
                $legalKeys,
            ),
            'footerNavChoices' => $this->orderedNavigationChoices(
                $nativeNavigationItems,
                is_array($nativeNavigation['footer'] ?? null) ? $nativeNavigation['footer'] : [],
                $legalKeys,
            ),
            'hamburgerNavChoices' => $this->orderedNavigationChoices(
                $nativeNavigationItems,
                is_array($nativeNavigation['hamburger'] ?? null) ? $nativeNavigation['hamburger'] : [],
            ),
            'termsUrl' => $termsUrl,
            'privacyUrl' => $privacyUrl,
        ];
    }

    /**
     * 選択済みを先頭に、保存順を維持した選択肢を返す。
     *
     * @param  list<array{key: string, label: string, url?: string}>  $items
     * @param  list<mixed>  $selectedKeys
     * @param  list<string>  $excludeKeys
     * @return list<array{key: string, label: string, url?: string, selected: bool}>
     */
    private function orderedNavigationChoices(array $items, array $selectedKeys, array $excludeKeys = []): array
    {
        $byKey = collect($items)
            ->filter(fn ($item) => is_array($item) && is_string($item['key'] ?? null))
            ->keyBy('key');
        $ordered = [];
        foreach ($selectedKeys as $key) {
            if (! is_string($key) || in_array($key, $excludeKeys, true) || ! $byKey->has($key)) {
                continue;
            }
            $item = $byKey->pull($key);
            $ordered[] = array_merge($item, ['selected' => true]);
        }
        foreach ($byKey as $key => $item) {
            if (in_array($key, $excludeKeys, true)) {
                continue;
            }
            $ordered[] = array_merge($item, ['selected' => false]);
        }

        return $ordered;
    }

    private function normalizeEnabled(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
