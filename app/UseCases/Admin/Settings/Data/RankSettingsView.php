<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * ランク設定画面用の表示データ。
 * @param array<int, array{key: string, label: string, min_points: int, order: int, is_active: bool, icon_url?: string|null, stamp_background_url?: string|null}> $rankSettings
 */
class RankSettingsView
{
    public function __construct(
        public array $rankSettings,
        public string $rankBasis,
        public int $pointEarnRate,
        public int $pointsValidDays
    ) {
    }
}
