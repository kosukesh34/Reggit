<?php

namespace App\Support;

class ShopSettingLiffDefaults
{
    public static function defaultLiffBuilderTemplates(): array
    {
        return [
            [
                'id' => 'welcome',
                'name' => 'ウェルカム',
                'blocks' => [
                    ['type' => 'hero', 'title' => 'ようこそ', 'body' => 'スマホで快適に注文・予約ができます。', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1200', 'button_label' => 'メニューを見る', 'button_url' => '#products'],
                    ['type' => 'text', 'title' => 'お知らせ', 'body' => '初回登録でクーポン配布中。'],
                    ['type' => 'button', 'title' => '会員登録', 'button_label' => '会員証を作成', 'button_url' => '#member'],
                ],
            ],
            [
                'id' => 'campaign',
                'name' => 'キャンペーン',
                'blocks' => [
                    ['type' => 'hero', 'title' => '期間限定キャンペーン', 'body' => '今週だけの特別メニュー。', 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1200'],
                    ['type' => 'list', 'title' => 'おすすめ', 'list_items' => ['季節限定バーガー', 'スペシャルドリンク', '数量限定クーポン']],
                    ['type' => 'button', 'button_label' => 'クーポンを確認', 'button_url' => '#coupons'],
                ],
            ],
            [
                'id' => 'reservation',
                'name' => '予約案内',
                'blocks' => [
                    ['type' => 'hero', 'title' => '事前予約でスムーズに', 'body' => '予約枠を確認して、希望の時間を押さえましょう。', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200'],
                    ['type' => 'button', 'button_label' => '予約する', 'button_url' => '#reservations'],
                    ['type' => 'text', 'title' => '注意事項', 'body' => '当日キャンセルはお控えください。'],
                ],
            ],
            [
                'id' => 'gallery',
                'name' => 'ギャラリー',
                'blocks' => [
                    ['type' => 'text', 'title' => '店内・商品ギャラリー', 'body' => '最新の写真をチェック。'],
                    ['type' => 'gallery', 'source' => 'gallery'],
                ],
            ],
            [
                'id' => 'form',
                'name' => '問い合わせ',
                'blocks' => [
                    ['type' => 'text', 'title' => 'お問い合わせ', 'body' => 'ご意見・ご要望をお聞かせください。'],
                    ['type' => 'form', 'title' => '簡単フォーム', 'form_fields' => [
                        ['label' => 'お名前', 'type' => 'text', 'placeholder' => '例: 山田 太郎', 'required' => true],
                        ['label' => 'メール', 'type' => 'email', 'placeholder' => 'example@example.com', 'required' => true],
                        ['label' => '内容', 'type' => 'textarea', 'placeholder' => 'ご用件', 'required' => true],
                    ]],
                ],
            ],
        ];
    }

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

            if ($blocks === []) {
                $normalized[$tab] = $defaultBlocks;

                continue;
            }

            $normalized[$tab] = $blocks;
        }

        return $normalized;
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

    private static function defaultLiffCafePatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-cafe');
    }

    private static function defaultLiffFastPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-order');
    }

    public static function defaultLiffSalonPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-salon');
    }

    private static function defaultLiffRetailPatternLayouts(): array
    {
        return self::layoutsFromSuite('rg-suite-retail');
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
