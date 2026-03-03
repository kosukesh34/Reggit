<?php

namespace App\UseCases\Admin\Settings\Data;

use Illuminate\Support\Collection;

/**
 * ランク設定画面用の表示データ。
 *
 * @param  array<int, array{key: string, label: string, min_points: int, order: int, is_active: bool, icon_url?: string|null, stamp_background_url?: string|null, card_color?: string|null}>  $rankSettings
 * @param  Collection<int, \App\Models\StampCard>  $stampCardsForSelect
 */
class RankSettingsView
{
    public function __construct(
        public array $rankSettings,
        public string $rankBasis,
        public int $pointEarnRate,
        public int $pointsValidDays,
        public string $memberCodeType,
        public string $memberCodeFormat,
        public int $memberCodeLength,
        public string $memberCodePrefix,
        public bool $showPointsDisplay,
        public bool $showRankProgress,
        public bool $showMemberCardForGuest,
        public string $memberCardDesign = 'default',
        public ?string $memberCardLogoUrl = null,
        public string $memberCardDisplayType = 'card',
        public string $memberCardCodeType = 'barcode',
        public int $pointsPerOrder = 0,
        public int $stampEarnPerAmount = 0,
        public ?int $orderStampCardId = null,
        public ?Collection $stampCardsForSelect = null
    ) {}
}
