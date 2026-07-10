<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * LIFFデザイン更新ユースケースへの入力。
 * 検証済み・JSON デコード済み。不変。
 *
 * @param  array<string, string|int|float|bool|array|null>  $layouts  レイアウト設定（JSON由来）
 * @param  array<string, string|int|float|bool|array|null>|null  $tabSettings  タブ設定（JSON由来）
 * @param  array<int, array{tab: string, label: string, icon: string, order: int, enabled: bool}>|null  $menuItems
 */
class UpdateLiffDesignerInput
{
    public function __construct(
        public readonly array $layouts,
        public readonly string $customCss,
        public readonly ?array $tabSettings,
        public readonly ?array $menuItems = null
    ) {}
}
