<?php

namespace App\Domain\Member\Services;

final class MemberRankDownGuard
{
    /**
     * 期間集計かつランクダウン無効のとき、保存済みランクより下げない。
     *
     * @param  array{current_rank: string, current_rank_label: string, current_rank_icon: string|null, current_rank_background: string|null, current_rank_color?: string|null, next_rank: string|null, next_rank_label: string|null, points_to_next_rank: int|null, rank_basis: string, rank_metric: int}  $info
     * @param  list<array{is_active?: bool|int|string, min_points?: int, order?: int, key?: string, label?: string, icon_url?: string|null, stamp_background_url?: string|null, card_color?: string|null}>  $ranks
     * @return array{current_rank: string, current_rank_label: string, current_rank_icon: string|null, current_rank_background: string|null, current_rank_color?: string|null, next_rank: string|null, next_rank_label: string|null, points_to_next_rank: int|null, rank_basis: string, rank_metric: int}
     */
    public function keepIfHigher(array $info, array $ranks, ?string $storedRank): array
    {
        if ($storedRank === null || $storedRank === '' || (string) $info['current_rank'] === $storedRank) {
            return $info;
        }
        $stored = $this->activeRankByKey($ranks, $storedRank);
        if ($stored === null) {
            return $info;
        }
        $resolvedMin = (int) ($this->activeRankByKey($ranks, (string) $info['current_rank'])['min_points'] ?? 0);
        if ((int) ($stored['min_points'] ?? 0) <= $resolvedMin) {
            return $info;
        }

        return $this->infoForKeptRank($info, $ranks, $stored);
    }

    /**
     * @param  list<array{is_active?: bool|int|string, min_points?: int, order?: int, key?: string, label?: string, icon_url?: string|null, stamp_background_url?: string|null, card_color?: string|null}>  $ranks
     * @return array{is_active?: bool|int|string, min_points?: int, order?: int, key?: string, label?: string, icon_url?: string|null, stamp_background_url?: string|null, card_color?: string|null}|null
     */
    private function activeRankByKey(array $ranks, string $key): ?array
    {
        foreach ($ranks as $rank) {
            if ($this->isActive($rank) && (string) ($rank['key'] ?? '') === $key) {
                return $rank;
            }
        }

        return null;
    }

    /**
     * @param  array{current_rank: string, current_rank_label: string, current_rank_icon: string|null, current_rank_background: string|null, current_rank_color?: string|null, next_rank: string|null, next_rank_label: string|null, points_to_next_rank: int|null, rank_basis: string, rank_metric: int}  $info
     * @param  list<array{is_active?: bool|int|string, min_points?: int, order?: int, key?: string, label?: string, icon_url?: string|null, stamp_background_url?: string|null, card_color?: string|null}>  $ranks
     * @param  array{min_points?: int, key?: string, label?: string, icon_url?: string|null, stamp_background_url?: string|null, card_color?: string|null}  $stored
     * @return array{current_rank: string, current_rank_label: string, current_rank_icon: string|null, current_rank_background: string|null, current_rank_color?: string|null, next_rank: string|null, next_rank_label: string|null, points_to_next_rank: int|null, rank_basis: string, rank_metric: int}
     */
    private function infoForKeptRank(array $info, array $ranks, array $stored): array
    {
        $storedMin = (int) ($stored['min_points'] ?? 0);
        $next = $this->nextRankAfter($ranks, $storedMin);
        $info['current_rank'] = (string) ($stored['key'] ?? $info['current_rank']);
        $info['current_rank_label'] = (string) ($stored['label'] ?? $info['current_rank']);
        $info['current_rank_icon'] = $stored['icon_url'] ?? null;
        $info['current_rank_background'] = $stored['stamp_background_url'] ?? null;
        $info['current_rank_color'] = $stored['card_color'] ?? null;
        $info['next_rank'] = $next !== null ? ($next['key'] ?? null) : null;
        $info['next_rank_label'] = $next !== null ? ($next['label'] ?? ($next['key'] ?? null)) : null;
        $info['points_to_next_rank'] = $next !== null
            ? max(0, (int) ($next['min_points'] ?? 0) - (int) $info['rank_metric'])
            : null;

        return $info;
    }

    /**
     * @param  list<array{is_active?: bool|int|string, min_points?: int, order?: int, key?: string, label?: string}>  $ranks
     * @return array{min_points?: int, key?: string, label?: string}|null
     */
    private function nextRankAfter(array $ranks, int $storedMin): ?array
    {
        $active = array_values(array_filter($ranks, fn ($rank) => $this->isActive($rank)));
        usort($active, fn ($a, $b) => ($a['min_points'] <=> $b['min_points']) ?: (($a['order'] ?? 0) <=> ($b['order'] ?? 0)));
        foreach ($active as $rank) {
            if ((int) ($rank['min_points'] ?? 0) > $storedMin) {
                return $rank;
            }
        }

        return null;
    }

    /** @param array{is_active?: bool|int|string} $rank */
    private function isActive(array $rank): bool
    {
        return ($rank['is_active'] ?? false) === true || ($rank['is_active'] ?? null) === 1 || ($rank['is_active'] ?? null) === '1';
    }
}
