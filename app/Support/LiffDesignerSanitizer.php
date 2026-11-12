<?php

namespace App\Support;

class LiffDesignerSanitizer
{
    /** タブメニュー（表示/非表示・並び替え・名前/アイコン変更）の対象タブ */
    private const MENU_TABS = [
        'home', 'products', 'shop', 'orders', 'stamps', 'coupons', 'tickets',
        'queue', 'reservations', 'member', 'profile', 'gallery', 'announcements',
    ];

    /** 外部Webページタブ（管理者がURLを設定するカスタムタブ）のキー */
    public const LINK_MENU_TABS = ['link_1', 'link_2', 'link_3'];

    /**
     * タブメニュー項目（表示/非表示・並び替え・名前/アイコン変更）をサニタイズする。
     * frontend_menu_items と同じ構造（tab / label / icon / order / enabled）を返す。
     *
     * @param  array<int, mixed>  $items
     * @return array<int, array{tab: string, label: string, icon: string, order: int, enabled: bool}>
     */
    public static function sanitizeMenuItems(array $items): array
    {
        $safe = [];
        $seen = [];
        $order = 0;
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $tab = (string) ($row['tab'] ?? '');
            $isLinkTab = in_array($tab, self::LINK_MENU_TABS, true);
            if ((! in_array($tab, self::MENU_TABS, true) && ! $isLinkTab) || isset($seen[$tab])) {
                continue;
            }

            $label = self::text($row['label'] ?? '', 12);
            $icon = (string) ($row['icon'] ?? '');
            if (preg_match('/^fa-[a-z0-9-]{1,40}$/i', $icon) !== 1) {
                $icon = '';
            }

            $item = [
                'tab' => $tab,
                'label' => $label,
                'icon' => $icon,
                'order' => $order,
                'enabled' => ! isset($row['enabled']) || $row['enabled'] !== false,
            ];

            if ($isLinkTab) {
                $url = self::text($row['url'] ?? '', 1000);
                if (preg_match('/^https:\/\//i', $url) !== 1) {
                    continue;
                }
                $item['url'] = $url;
                $item['open'] = ($row['open'] ?? 'embed') === 'external' ? 'external' : 'embed';
                $item['label'] = $label !== '' ? $label : 'リンク';
                $item['icon'] = $icon !== '' ? $icon : 'fa-globe';
            }

            $seen[$tab] = true;
            $safe[] = $item;
            $order++;
        }

        return $safe;
    }

    private static function text($v, int $max = 255): string
    {
        $s = is_scalar($v) ? (string) $v : '';
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';

        return trim(mb_substr($s, 0, $max));
    }
}
