<?php

namespace App\Support;

class ShopSettingLiffDefaults
{
    public static function defaultLiffDesignerTemplates(): array
    {
        return LiffAppStudioTemplates::catalog();
    }

    public static function defaultLiffTabLayouts(): array
    {
        return LiffAppStudioTemplates::defaultLayouts();
    }

    /**
     * 詳細ページ用レイアウト（タブと並列）。product_detail 等は今後同じ枠に追加する。
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function defaultLiffDetailLayouts(): array
    {
        return [
            'announcement_detail' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => 'お知らせ', 'size' => 'medium'],
                ['type' => 'data', 'source' => 'announcement_detail'],
            ],
            'product_detail' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => '商品詳細', 'size' => 'medium'],
                ['type' => 'data', 'source' => 'product_detail'],
            ],
            'coupon_detail' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => 'クーポン', 'size' => 'medium'],
                ['type' => 'data', 'source' => 'coupon_detail'],
            ],
            'gallery_detail' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => 'ギャラリー', 'size' => 'medium'],
                ['type' => 'data', 'source' => 'gallery_detail'],
            ],
            'stamp_detail' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => 'スタンプ', 'size' => 'medium'],
                ['type' => 'data', 'source' => 'stamp_detail'],
            ],
            'order_cart' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => 'カート', 'size' => 'medium'],
                ['type' => 'text', 'design' => 'body', 'text' => 'カート内容はアプリ内で表示されます。店舗設定で商品・オプションを差し替えできます。'],
            ],
            'order_confirm' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => '注文確認', 'size' => 'medium'],
                ['type' => 'text', 'design' => 'body', 'text' => '注文確認はアプリ内で表示されます。'],
            ],
            'order_complete' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => '注文完了', 'size' => 'medium'],
                ['type' => 'text', 'design' => 'body', 'text' => '注文完了画面です。受取案内やポイント付与メッセージを編集できます。'],
            ],
            'reservation_new' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => '予約する', 'size' => 'medium'],
                ['type' => 'text', 'design' => 'body', 'text' => '予約フォームはアプリ内で表示されます。'],
            ],
            'reservation_confirm' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => '予約確認', 'size' => 'medium'],
                ['type' => 'text', 'design' => 'body', 'text' => '予約確認はアプリ内で表示されます。'],
            ],
            'reservation_complete' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => '予約完了', 'size' => 'medium'],
                ['type' => 'text', 'design' => 'body', 'text' => '予約完了画面です。来店案内メッセージを編集できます。'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $layouts
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function ensureLiffDetailLayouts(array $layouts): array
    {
        $defaults = self::defaultLiffDetailLayouts();
        $normalized = [];
        foreach ($defaults as $key => $defaultBlocks) {
            $rows = $layouts[$key] ?? null;
            $blocks = is_array($rows) ? array_values(array_filter($rows, fn ($v) => is_array($v))) : [];
            $normalized[$key] = $blocks === [] ? $defaultBlocks : $blocks;
        }
        foreach ($layouts as $key => $rows) {
            if (isset($normalized[$key]) || ! is_string($key)) {
                continue;
            }
            $blocks = is_array($rows) ? array_values(array_filter($rows, fn ($v) => is_array($v))) : [];
            if ($blocks !== []) {
                $normalized[$key] = $blocks;
            }
        }

        return $normalized;
    }

    /**
     * 保存済みレイアウトをタブ単位で正規化する。
     *
     * ルール:
     * - タブが空ならデフォルトレイアウトを採用
     * - products が交換カタログのコピーなら注文用デフォルトに置き換える
     * - 既存ブロックは順序を変えずに維持する
     *
     * @param  array<string, mixed>  $layouts
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function ensureLiffTabLayouts(array $layouts): array
    {
        $defaults = self::defaultLiffTabLayouts();

        $normalized = [];
        foreach ($defaults as $tab => $defaultBlocks) {
            $rows = $layouts[$tab] ?? null;
            $blocks = is_array($rows) ? array_values(array_filter($rows, fn ($v) => is_array($v))) : [];

            if ($blocks === [] || ($tab === 'products' && self::isPointExchangeProductsLayout($blocks))) {
                $normalized[$tab] = $defaultBlocks;

                continue;
            }

            $normalized[$tab] = $blocks;
        }

        return $normalized;
    }

    /**
     * products タブはモバイル注文画面。会員OSの交換カタログコピーは注文UIに出さない。
     *
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private static function isPointExchangeProductsLayout(array $blocks): bool
    {
        foreach ($blocks as $block) {
            $text = (string) ($block['text'] ?? '');
            if ($text === '交換メニュー' || $text === '特典で交換') {
                return true;
            }
        }

        return false;
    }

    public static function defaultLiffTabSettings(): array
    {
        $theme = LiffAppStudioTemplates::theme([
            'content_padding' => 18,
            'section_gap' => 20,
            'require_membership' => true,
        ]);
        $settings = ['_global' => $theme];
        foreach (LiffAppStudioTemplates::emptyTabs() as $tab => $bg) {
            $settings[$tab] = $bg;
        }
        foreach ([
            'announcement_detail', 'product_detail', 'coupon_detail', 'gallery_detail', 'stamp_detail',
            'order_cart', 'order_confirm', 'order_complete',
            'reservation_new', 'reservation_confirm', 'reservation_complete',
        ] as $detailTab) {
            $settings[$detailTab] = ['background_color' => '', 'background_image' => ''];
        }

        return $settings;
    }

    public static function defaultLiffSalonPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-salon');
    }

    public static function defaultLiffFitnessPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-loyalty');
    }

    public static function defaultLiffHotelPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-booking');
    }

    public static function defaultLiffClinicPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-clinic');
    }

    public static function defaultLiffLuxuryRetailPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-retail');
    }

    public static function defaultLiffDeliveryPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-delivery');
    }

    public static function defaultLiffSweetsPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-sweets');
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function layoutsFromSuite(string $suiteId): array
    {
        foreach (LiffAppStudioTemplates::catalog() as $tpl) {
            if (($tpl['id'] ?? '') === $suiteId && isset($tpl['layouts']) && is_array($tpl['layouts'])) {
                return $tpl['layouts'];
            }
        }

        return LiffAppStudioTemplates::defaultLayouts();
    }
}
