<?php

namespace App\Services;

use App\Models\Member;
use App\Models\ShopSetting;
use App\Util\AdminContext;

class MemberRankCalculator
{
    /**
     * @return array{current_rank: string, current_rank_label: string, current_rank_icon: string|null, current_rank_background: string|null, next_rank: string|null, next_rank_label: string|null, points_to_next_rank: int|null, rank_basis: string, rank_metric: int}
     */
    public function getRankInfo(Member $member): array
    {
        $projectId = $member->project_id ?? (AdminContext::getCurrentProjectId() ?? 1);
        $ranks = ShopSetting::getMemberRankSettings($projectId);
        $basis = ShopSetting::getRankBasis($projectId);

        $points = $member->points ?? 0;
        $stamps = $member->getTotalStamps();
        $metric = $basis === 'stamps' ? $stamps : ($basis === 'max' ? max($points, $stamps) : $points);

        $active = array_values(array_filter($ranks, fn ($rank) => $this->isRankActive($rank)));
        if (count($active) === 0) {
            return [
                'current_rank' => $member->rank ?? 'bronze',
                'current_rank_label' => $member->rank ?? 'bronze',
                'current_rank_icon' => null,
                'current_rank_background' => null,
                'current_rank_color' => null,
                'next_rank' => null,
                'next_rank_label' => null,
                'points_to_next_rank' => null,
                'rank_basis' => $basis,
                'rank_metric' => $metric,
            ];
        }

        usort($active, fn ($a, $b) => ($a['min_points'] <=> $b['min_points']) ?: ($a['order'] <=> $b['order']));
        $resolved = $active[0];
        foreach ($active as $rank) {
            if ($metric >= ($rank['min_points'] ?? 0)) {
                $resolved = $rank;
            }
        }

        $next = null;
        foreach ($active as $rank) {
            if (($rank['min_points'] ?? 0) > ($resolved['min_points'] ?? 0)) {
                $next = $rank;
                break;
            }
        }

        $pointsToNext = null;
        if ($next !== null) {
            $pointsToNext = max(0, ($next['min_points'] ?? 0) - $metric);
        }

        return [
            'current_rank' => $resolved['key'] ?? 'bronze',
            'current_rank_label' => $resolved['label'] ?? ($resolved['key'] ?? 'bronze'),
            'current_rank_icon' => $resolved['icon_url'] ?? null,
            'current_rank_background' => $resolved['stamp_background_url'] ?? null,
            'current_rank_color' => $resolved['card_color'] ?? null,
            'next_rank' => $next !== null ? ($next['key'] ?? null) : null,
            'next_rank_label' => $next !== null ? ($next['label'] ?? ($next['key'] ?? null)) : null,
            'points_to_next_rank' => $pointsToNext,
            'rank_basis' => $basis,
            'rank_metric' => $metric,
        ];
    }

    public function resolveCurrentRank(Member $member): string
    {
        $info = $this->getRankInfo($member);

        return (string) ($info['current_rank'] ?? $member->rank ?? 'bronze');
    }

    /** @param array{is_active?: bool|int|string, min_points?: int, order?: int, key?: string, label?: string} $rank */
    private function isRankActive(array $rank): bool
    {
        if (! array_key_exists('is_active', $rank)) {
            return false;
        }

        return $rank['is_active'] === true || $rank['is_active'] === 1 || $rank['is_active'] === '1';
    }
}
