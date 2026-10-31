<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Enums\RankAggregationType;
use App\Util\StrHelper;

/** ポイント・スタンプの付与率と会員ランクの設定。 */
class MemberRankSettingStore
{
    public function __construct(
        private ShopSettingStore $values,
    ) {}

    public function getPointEarnRate(?int $projectId = null): int
    {
        return (int) $this->values->getValue('point_earn_rate', 100, $projectId);
    }

    /** 注文ごとの固定ポイント（0で無効） */
    public function getPointsPerOrder(?int $projectId = null): int
    {
        return (int) $this->values->getValue('points_per_order', 0, $projectId);
    }

    /** スタンプ1個付与につき何円か（0で無効）。例: 1000 = 1000円で1スタンプ */
    public function getStampEarnPerAmount(?int $projectId = null): int
    {
        return (int) $this->values->getValue('stamp_earn_per_amount', 0, $projectId);
    }

    /** 注文時にスタンプを付与するスタンプカードID（nullで付与しない） */
    public function getOrderStampCardId(?int $projectId = null): ?int
    {
        $v = $this->values->getValue('order_stamp_card_id', null, $projectId);
        if ($v === null || $v === '') {
            return null;
        }
        $id = (int) $v;

        return $id > 0 ? $id : null;
    }

    public function getPointsValidDays(?int $projectId = null): int
    {
        return (int) $this->values->getValue('points_valid_days', 365, $projectId);
    }

    public function getRankBasis(?int $projectId = null): string
    {
        return (string) $this->values->getValue('member_rank_basis', 'points', $projectId);
    }

    public function getUseStamps(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('use_stamps', true, $projectId);
    }

    public function getUseTransactionStamps(?int $projectId = null): bool
    {
        return $this->getUseStamps($projectId) && (bool) $this->values->getValue('use_transaction_stamps', true, $projectId);
    }

    public function getUsePoints(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('use_points', true, $projectId);
    }

    public function getRankAggregationType(?int $projectId = null): string
    {
        return RankAggregationType::normalize((string) $this->values->getValue('member_rank_aggregation_type', RankAggregationType::TOTAL, $projectId));
    }

    public function getRankPeriodDays(?int $projectId = null): int
    {
        $days = (int) $this->values->getValue('member_rank_period_days', 365, $projectId);

        return $days > 0 ? $days : 365;
    }

    public function getIsRankDownEnabled(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('is_rank_down_enabled', false, $projectId);
    }

    public function getMemberRankSettings(?int $projectId = null): array
    {
        $settings = $this->values->getValue('member_rank_settings', null, $projectId);
        if (! is_array($settings) || $settings === []) {
            return $this->defaultMemberRankSettings();
        }
        $normalized = [];
        foreach ($settings as $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = $row['key'] ?? null;
            if (StrHelper::isEmptyStringOrNull($key)) {
                continue;
            }
            $normalized[] = [
                'key' => (string) $key,
                'label' => (string) ($row['label'] ?? $key),
                'min_points' => (int) ($row['min_points'] ?? 0),
                'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : true,
                'card_color' => isset($row['card_color']) && $row['card_color'] !== '' ? (string) $row['card_color'] : null,
                'order' => (int) ($row['order'] ?? 0),
                'point_earn_rate' => isset($row['point_earn_rate']) && $row['point_earn_rate'] !== null && $row['point_earn_rate'] !== ''
                    ? (int) $row['point_earn_rate']
                    : null,
                'stamp_earn_per_amount' => array_key_exists('stamp_earn_per_amount', $row)
                    && $row['stamp_earn_per_amount'] !== null
                    && $row['stamp_earn_per_amount'] !== ''
                    ? (int) $row['stamp_earn_per_amount']
                    : null,
                'smaregi_rank_id' => isset($row['smaregi_rank_id']) && trim((string) $row['smaregi_rank_id']) !== ''
                    ? (string) $row['smaregi_rank_id']
                    : null,
            ];
        }
        if ($normalized === []) {
            return $this->defaultMemberRankSettings();
        }
        usort($normalized, fn ($a, $b) => ($a['min_points'] <=> $b['min_points']) ?: ($a['order'] <=> $b['order']));

        return $normalized;
    }

    /**
     * ランク別の付与レート（円/1pt）。未設定時はプロジェクト全体のデフォルトを返す。
     */
    public function getPointEarnRateForRank(?int $projectId, ?string $rankKey): int
    {
        $default = $this->getPointEarnRate($projectId);
        if ($rankKey === null || $rankKey === '') {
            return $default;
        }
        foreach ($this->getMemberRankSettings($projectId) as $rank) {
            if ((string) ($rank['key'] ?? '') !== $rankKey) {
                continue;
            }
            $rate = $rank['point_earn_rate'] ?? null;
            if ($rate !== null && (int) $rate > 0) {
                return (int) $rate;
            }

            return $default;
        }

        return $default;
    }

    /**
     * ランク別のスタンプ付与レート（円/1スタンプ）。未設定時はプロジェクト全体のデフォルト。0 は付与しない。
     */
    public function getStampEarnPerAmountForRank(?int $projectId, ?string $rankKey): int
    {
        $default = $this->getStampEarnPerAmount($projectId);
        if ($rankKey === null || $rankKey === '') {
            return $default;
        }
        foreach ($this->getMemberRankSettings($projectId) as $rank) {
            if ((string) ($rank['key'] ?? '') !== $rankKey) {
                continue;
            }
            if (! array_key_exists('stamp_earn_per_amount', $rank) || $rank['stamp_earn_per_amount'] === null) {
                return $default;
            }

            return max(0, (int) $rank['stamp_earn_per_amount']);
        }

        return $default;
    }

    public function setMemberRankSettings(array $settings, ?int $projectId = null): void
    {
        $this->values->setValue('member_rank_settings', $settings, 'json', $projectId);
    }

    public function defaultMemberRankSettings(): array
    {
        return [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => true, 'card_color' => '#cd7f32', 'order' => 1, 'point_earn_rate' => null, 'stamp_earn_per_amount' => null, 'smaregi_rank_id' => null],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 1000, 'is_active' => true, 'card_color' => '#c0c0c0', 'order' => 2, 'point_earn_rate' => null, 'stamp_earn_per_amount' => null, 'smaregi_rank_id' => null],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 5000, 'is_active' => true, 'card_color' => '#ffd700', 'order' => 3, 'point_earn_rate' => null, 'stamp_earn_per_amount' => null, 'smaregi_rank_id' => null],
            ['key' => 'platinum', 'label' => 'プラチナ', 'min_points' => 10000, 'is_active' => true, 'card_color' => '#e5e4e2', 'order' => 4, 'point_earn_rate' => null, 'stamp_earn_per_amount' => null, 'smaregi_rank_id' => null],
        ];
    }
}
