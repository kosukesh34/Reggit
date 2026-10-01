<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * ランク設定画面用の表示データ。
 *
 * @param  array<int, array{key: string, label: string, min_points: int, order: int, is_active: bool, card_color?: string|null, point_earn_rate?: int|null, stamp_earn_per_amount?: int|null}>  $rankSettings
 */
final readonly class RankSettingsView
{
    public function __construct(
        public array $rankSettings,
        public string $rankBasis,
        public int $pointEarnRate,
        public int $pointsValidDays,
        public int $pointsPerOrder = 0,
        public int $stampEarnPerAmount = 0,
        public string $rankAggregationType = 'total',
        public int $rankPeriodDays = 365,
        public bool $isRankDownEnabled = false,
    ) {}
}
