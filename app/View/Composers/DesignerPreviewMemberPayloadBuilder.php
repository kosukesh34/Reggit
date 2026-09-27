<?php

namespace App\View\Composers;

use App\Domain\Settings\Repositories\MemberCardPreviewSettings;

final class DesignerPreviewMemberPayloadBuilder
{
    public function __construct(private readonly MemberCardPreviewSettings $settings) {}

    public function build(int $projectId): array
    {
        $ranks = $this->settings->getMemberRankSettings($projectId);
        $activeRanks = array_values(array_filter($ranks, fn ($rank) => ($rank['is_active'] ?? false) === true));
        usort($activeRanks, fn ($left, $right) => ($left['min_points'] <=> $right['min_points']) ?: (($left['order'] ?? 0) <=> ($right['order'] ?? 0)));

        $displayRank = $activeRanks[0] ?? [
            'key' => 'bronze',
            'label' => 'ブロンズ',
            'min_points' => 0,
            'card_color' => '#cd7f32',
        ];
        $nextRank = null;
        foreach ($activeRanks as $rank) {
            if (($rank['min_points'] ?? 0) > ($displayRank['min_points'] ?? 0)) {
                $nextRank = $rank;
                break;
            }
        }

        $samplePoints = (int) ($displayRank['min_points'] ?? 0) + 500;
        $pointsToNext = $nextRank !== null
            ? max(0, ((int) ($nextRank['min_points'] ?? 0)) - $samplePoints)
            : null;

        return [
            'is_member' => true,
            'member' => ['member_number' => '000123456789', 'points' => $samplePoints],
            'user' => ['name' => 'プレビュー会員'],
            'points' => $samplePoints,
            'current_rank' => $displayRank['key'] ?? 'bronze',
            'current_rank_label' => $displayRank['label'] ?? 'ブロンズ',
            'current_rank_icon' => null,
            'current_rank_color' => isset($displayRank['card_color']) && $displayRank['card_color'] !== '' ? (string) $displayRank['card_color'] : null,
            'next_rank_label' => $nextRank !== null ? ($nextRank['label'] ?? null) : null,
            'points_to_next_rank' => $pointsToNext,
            'days_to_next_rank' => null,
            'days_required_for_next_rank' => $this->settings->getRankAggregationType($projectId) === 'period'
                ? $this->settings->getRankPeriodDays($projectId)
                : null,
            'rank_basis' => $this->settings->getRankBasis($projectId),
            'rank_aggregation_type' => $this->settings->getRankAggregationType($projectId),
            'show_points_display' => $this->settings->getShowPointsDisplay($projectId)
                && $this->settings->getUsePoints($projectId),
            'show_rank_progress' => $this->settings->getShowRankProgress($projectId),
            'member_card_design' => $this->settings->getMemberCardDesign($projectId),
            'member_card_logo_url' => $this->settings->getFrontendLogoUrl($projectId),
            'member_card_display_type' => $this->settings->getMemberCardDisplayType($projectId),
            'member_card_code_type' => $this->settings->getMemberCardCodeType($projectId),
            'points_expiry' => ['expires_at_label' => '2026/12/31', 'points_expiring' => 200],
            'rank_up_history' => [
                ['from_rank_label' => 'ブロンズ', 'to_rank_label' => 'シルバー', 'achieved_at' => '2025/11/03'],
                ['from_rank_label' => 'シルバー', 'to_rank_label' => $displayRank['label'] ?? 'ゴールド', 'achieved_at' => '2026/01/20'],
            ],
        ];
    }
}
