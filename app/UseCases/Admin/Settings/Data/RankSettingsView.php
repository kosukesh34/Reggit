<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * ランク設定画面用の表示データ。
 * @param array<int, array{key: string, label: string, min_points: int, order: int, is_active: bool, icon_url?: string|null, stamp_background_url?: string|null, card_color?: string|null}> $rankSettings
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
        /** 会員証デザイン: default, logo_top, logo_center */
        public string $memberCardDesign = 'default',
        /** プレビュー用・会員証に表示するロゴURL（frontend_logo_url） */
        public ?string $memberCardLogoUrl = null
    ) {
    }
}
