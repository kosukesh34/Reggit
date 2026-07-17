<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * LIFFデザイン画面用の表示データ。
 *
 * @param  array<string, string|int|float|bool|array|null>  $layouts  レイアウト設定（JSON由来）
 * @param  array<string, string|int|float|bool|array|null>  $templates  テンプレート設定（JSON由来）
 * @param  array<string, string|int|float|bool|array|null>  $tabSettings  タブ設定（JSON由来）
 * @param  array<string, bool>  $featureFlags  機能フラグ
 * @param  array<int, array<string, mixed>>  $menuItems  タブメニュー項目（非表示タブ含む全件・order順）
 * @param  array<string, array<int, array<string, mixed>>>  $detailLayouts  詳細ページレイアウト
 */
class LiffDesignerView
{
    public function __construct(
        public readonly array $layouts,
        public readonly array $templates,
        public readonly string $customCss,
        public readonly array $tabSettings,
        public readonly array $featureFlags = [],
        public readonly string $lineFriendAddUrl = '',
        public readonly array $menuItems = [],
        public readonly array $detailLayouts = []
    ) {}
}
