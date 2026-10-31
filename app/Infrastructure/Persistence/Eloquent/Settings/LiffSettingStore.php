<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Support\LiffDesignerSanitizer;
use App\Support\MemberCardDesignMap;
use App\Support\ShopSettingLiffDefaults;
use App\Util\AdminContext;
use App\Util\StrHelper;

/** フロントのメニュー項目と LIFF 画面（タブ・詳細・保存画面・CSS）の設定。 */
class LiffSettingStore
{
    public function __construct(
        private ShopSettingStore $values,
        private MemberCardSettingStore $memberCardSettingStore,
    ) {}

    /** フロント（ユーザー向け）のヘッダー・フッターに表示するメニュー項目のデフォルト */
    public function defaultFrontendMenuItems(): array
    {
        return [
            ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-house', 'order' => 0],
            ['tab' => 'products', 'label' => 'オーダー', 'icon' => 'fa-utensils', 'order' => 1],
            ['tab' => 'shop', 'label' => 'ショップ', 'icon' => 'fa-shopping-bag', 'order' => 2],
            ['tab' => 'orders', 'label' => '注文', 'icon' => 'fa-receipt', 'order' => 3],
            ['tab' => 'stamps', 'label' => 'スタンプ', 'icon' => 'fa-stamp', 'order' => 4],
            ['tab' => 'coupons', 'label' => 'クーポン', 'icon' => 'fa-ticket-alt', 'order' => 5],
            ['tab' => 'tickets', 'label' => '回数券', 'icon' => 'fa-ticket', 'order' => 5],
            ['tab' => 'designlab', 'label' => 'あそび', 'icon' => 'fa-dice', 'order' => 6],
            ['tab' => 'queue', 'label' => '順番待ち', 'icon' => 'fa-list-ol', 'order' => 7],
            ['tab' => 'reservations', 'label' => '予約', 'icon' => 'fa-calendar-check', 'order' => 8],
            ['tab' => 'member', 'label' => '会員証', 'icon' => 'fa-id-card', 'order' => 9],
            ['tab' => 'profile', 'label' => 'プロフィール', 'icon' => 'fa-user', 'order' => 10],
            ['tab' => 'gallery', 'label' => 'ギャラリー', 'icon' => 'fa-images', 'order' => 11],
            ['tab' => 'announcements', 'label' => 'お知らせ', 'icon' => 'fa-bullhorn', 'order' => 12],
            ['tab' => 'chat', 'label' => 'チャット', 'icon' => 'fa-comments', 'order' => 13],
        ];
    }

    /** フロントメニュー項目を取得（ホーム設定で「表示する」にしたもののみ・order でソート） */
    public function getFrontendMenuItems(?int $projectId = null): array
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $items = $this->values->getValue('frontend_menu_items', null, $projectId);
        $defaults = collect($this->defaultFrontendMenuItems())->keyBy('tab');
        $merged = [];
        $saved = is_array($items) ? collect($items)->keyBy('tab') : collect();
        foreach ($defaults as $tab => $default) {
            $item = $saved->get($tab);
            $row = $item !== null ? array_merge($default, $item) : $default;
            if (array_key_exists('enabled', $row) && ! filter_var($row['enabled'], FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }
            $row['icon'] = $this->normalizeFrontendMenuIcon((string) $tab, (string) ($row['icon'] ?? ''));
            $row['label'] = (string) ($row['label'] ?? $default['label']);
            $merged[] = $row;
        }
        foreach (is_array($items) ? $items : [] as $item) {
            $tab = $item['tab'] ?? null;
            $isLinkTab = in_array($tab, LiffDesignerSanitizer::LINK_MENU_TABS, true);
            if (StrHelper::isEmptyStringOrNull($tab) || ($defaults->has($tab) === false && $isLinkTab === false)) {
                continue;
            }
            if ($isLinkTab) {
                if (array_key_exists('enabled', $item) && ! filter_var($item['enabled'], FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }
                if (! isset($item['url']) || preg_match('/^https:\/\//i', (string) $item['url']) !== 1) {
                    continue;
                }
                // 保存されたブラウザの選択を維持する。
                $item['open'] = ($item['open'] ?? 'embed') === 'external' ? 'external' : 'embed';
                $merged[] = $item;
            }
        }
        if ($merged === [] && ! is_array($items)) {
            return $this->defaultFrontendMenuItems();
        }
        usort($merged, fn ($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        return $merged;
    }

    /** 旧アイコン名を現行デフォルトへ寄せ、デザイナー／本番の見た目差を防ぐ */
    public function normalizeFrontendMenuIcon(string $tab, string $icon): string
    {
        $defaults = collect($this->defaultFrontendMenuItems())->keyBy('tab');
        $fallback = (string) ($defaults->get($tab)['icon'] ?? 'fa-circle');
        if ($icon === '') {
            return $fallback;
        }

        // A saved home icon on another built-in screen obscures its purpose.
        if ($tab !== 'home' && in_array($icon, ['fa-home', 'fa-house'], true)) {
            return $fallback;
        }

        $aliases = [
            'fa-home' => 'fa-house',
            'fa-calendar-alt' => 'fa-calendar-check',
            'fa-users' => 'fa-list-ol',
        ];
        if ($tab === 'member' && ($icon === 'fa-user' || $icon === 'fa-users')) {
            return 'fa-id-card';
        }
        if ($tab === 'queue' && $icon === 'fa-users') {
            return 'fa-list-ol';
        }

        return $aliases[$icon] ?? $icon;
    }

    /** フロントメニュー項目を保存 */
    public function setFrontendMenuItems(array $items, ?int $projectId = null): void
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $this->values->setValue('frontend_menu_items', $items, 'json', $projectId);
    }

    public function defaultLiffDesignerTemplates(): array
    {
        return ShopSettingLiffDefaults::defaultLiffDesignerTemplates();
    }

    public function getLiffTabLayouts(?int $projectId = null): array
    {
        $layouts = $this->values->getValue('liff_tab_layouts', null, $projectId);
        if (! is_array($layouts) || $layouts === []) {
            return ShopSettingLiffDefaults::defaultLiffTabLayouts();
        }

        return ShopSettingLiffDefaults::ensureLiffTabLayouts($layouts);
    }

    public function getLiffCustomCss(?int $projectId = null): string
    {
        return (string) $this->values->getValue('liff_custom_css', '', $projectId);
    }

    public function setLiffCustomCss(string $css, ?int $projectId = null): void
    {
        $this->values->setValue('liff_custom_css', $css, 'string', $projectId);
    }

    public function getLiffTabSettings(?int $projectId = null): array
    {
        $settings = $this->values->getValue('liff_tab_settings', null, $projectId);
        $settings = is_array($settings) && $settings !== []
            ? $settings
            : ShopSettingLiffDefaults::defaultLiffTabSettings();

        return $this->applyMemberCardRuntimeToTabSettings($settings, $projectId);
    }

    /**
     * ランク設定の会員証デザインを LIFF タブ設定へ反映する（読み取り時の正）。
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function applyMemberCardRuntimeToTabSettings(array $settings, ?int $projectId = null): array
    {
        $global = isset($settings['_global']) && is_array($settings['_global']) ? $settings['_global'] : [];
        $design = $this->memberCardSettingStore->getMemberCardDesign($projectId);
        $global['member_card_design'] = $design;
        $global['member_card_display_type'] = $this->memberCardSettingStore->getMemberCardDisplayType($projectId);
        $global['member_card_code_type'] = $this->memberCardSettingStore->getMemberCardCodeType($projectId);

        $template = MemberCardDesignMap::templateForDesign($design);
        if ($template !== null) {
            $customization = $global['app_customization'] ?? [];
            if (is_string($customization)) {
                $decoded = json_decode($customization, true);
                $customization = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($customization)) {
                $customization = [];
            }
            $membership = isset($customization['membership']) && is_array($customization['membership'])
                ? $customization['membership']
                : [];
            $membership['template'] = $template;
            $customization['membership'] = $membership;
            $global['app_customization'] = $customization;
        }

        $settings['_global'] = $global;

        return $settings;
    }

    public function setLiffTabSettings(array $settings, ?int $projectId = null): void
    {
        $this->values->setValue('liff_tab_settings', $settings, 'json', $projectId);
    }

    public function setLiffTabLayouts(array $layouts, ?int $projectId = null): void
    {
        $this->values->setValue('liff_tab_layouts', ShopSettingLiffDefaults::ensureLiffTabLayouts($layouts), 'json', $projectId);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getLiffDetailLayouts(?int $projectId = null): array
    {
        $layouts = $this->values->getValue('liff_detail_layouts', null, $projectId);
        if (! is_array($layouts) || $layouts === []) {
            return ShopSettingLiffDefaults::defaultLiffDetailLayouts();
        }

        return ShopSettingLiffDefaults::ensureLiffDetailLayouts($layouts);
    }

    /**
     * @param  array<string, mixed>  $layouts
     */
    public function setLiffDetailLayouts(array $layouts, ?int $projectId = null): void
    {
        $this->values->setValue('liff_detail_layouts', ShopSettingLiffDefaults::ensureLiffDetailLayouts($layouts), 'json', $projectId);
    }

    public function getLiffSavedScreens(?int $projectId = null): array
    {
        $screens = $this->values->getValue('liff_saved_screens', [], $projectId);

        return is_array($screens) ? $screens : [];
    }

    public function setLiffSavedScreens(array $screens, ?int $projectId = null): void
    {
        $this->values->setValue('liff_saved_screens', $screens, 'json', $projectId);
    }

    public function defaultLiffTabLayouts(): array
    {
        return ShopSettingLiffDefaults::defaultLiffTabLayouts();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function defaultLiffDetailLayouts(): array
    {
        return ShopSettingLiffDefaults::defaultLiffDetailLayouts();
    }

    /**
     * 保存済みレイアウトをタブ単位で正規化する。
     *
     * ルール:
     * - タブが空ならデフォルトレイアウトを採用
     * - 既存ブロックは順序を変えずに維持する
     *
     * @param  array<string, mixed>  $layouts
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function ensureLiffTabLayouts(array $layouts): array
    {
        return ShopSettingLiffDefaults::ensureLiffTabLayouts($layouts);
    }

    public function defaultLiffTabSettings(): array
    {
        return ShopSettingLiffDefaults::defaultLiffTabSettings();
    }
}
