<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Enums\RankAggregationType;
use App\Util\StrHelper;

/** ポイント・スタンプの付与率と会員ランクの設定。 */
class MemberRankSettingStore
{
    public static function getPointEarnRate(?int $projectId = null): int
    {
        return (int) ShopSettingStore::getValue('point_earn_rate', 100, $projectId);
    }

    /** 注文ごとの固定ポイント（0で無効） */
    public static function getPointsPerOrder(?int $projectId = null): int
    {
        return (int) ShopSettingStore::getValue('points_per_order', 0, $projectId);
    }

    /** スタンプ1個付与につき何円か（0で無効）。例: 1000 = 1000円で1スタンプ */
    public static function getStampEarnPerAmount(?int $projectId = null): int
    {
        return (int) ShopSettingStore::getValue('stamp_earn_per_amount', 0, $projectId);
    }

    /** 注文時にスタンプを付与するスタンプカードID（nullで付与しない） */
    public static function getOrderStampCardId(?int $projectId = null): ?int
    {
        $v = ShopSettingStore::getValue('order_stamp_card_id', null, $projectId);
        if ($v === null || $v === '') {
            return null;
        }
        $id = (int) $v;

        return $id > 0 ? $id : null;
    }

    public static function getPointsValidDays(?int $projectId = null): int
    {
        return (int) ShopSettingStore::getValue('points_valid_days', 365, $projectId);
    }

    public static function getRankBasis(?int $projectId = null): string
    {
        return (string) ShopSettingStore::getValue('member_rank_basis', 'points', $projectId);
    }

    public static function getUseStamps(?int $projectId = null): bool
    {
        return (bool) ShopSettingStore::getValue('use_stamps', true, $projectId);
    }

    public static function getUseTransactionStamps(?int $projectId = null): bool
    {
        return self::getUseStamps($projectId) && (bool) ShopSettingStore::getValue('use_transaction_stamps', true, $projectId);
    }

    public static function getUsePoints(?int $projectId = null): bool
    {
        return (bool) ShopSettingStore::getValue('use_points', true, $projectId);
    }

    public static function getRankAggregationType(?int $projectId = null): string
    {
        return RankAggregationType::normalize((string) ShopSettingStore::getValue('member_rank_aggregation_type', RankAggregationType::TOTAL, $projectId));
    }

    public static function getRankPeriodDays(?int $projectId = null): int
    {
        $days = (int) ShopSettingStore::getValue('member_rank_period_days', 365, $projectId);

        return $days > 0 ? $days : 365;
    }

    public static function getIsRankDownEnabled(?int $projectId = null): bool
    {
        return (bool) ShopSettingStore::getValue('is_rank_down_enabled', false, $projectId);
    }

    public static function getMemberRankSettings(?int $projectId = null): array
    {
        $settings = ShopSettingStore::getValue('member_rank_settings', null, $projectId);
        if (! is_array($settings) || $settings === []) {
            return self::defaultMemberRankSettings();
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
            return self::defaultMemberRankSettings();
        }
        usort($normalized, fn ($a, $b) => ($a['min_points'] <=> $b['min_points']) ?: ($a['order'] <=> $b['order']));

        return $normalized;
    }

    /**
     * ランク別の付与レート（円/1pt）。未設定時はプロジェクト全体のデフォルトを返す。
     */
    public static function getPointEarnRateForRank(?int $projectId, ?string $rankKey): int
    {
        $default = self::getPointEarnRate($projectId);
        if ($rankKey === null || $rankKey === '') {
            return $default;
        }
        foreach (self::getMemberRankSettings($projectId) as $rank) {
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
    public static function getStampEarnPerAmountForRank(?int $projectId, ?string $rankKey): int
    {
        $default = self::getStampEarnPerAmount($projectId);
        if ($rankKey === null || $rankKey === '') {
            return $default;
        }
        foreach (self::getMemberRankSettings($projectId) as $rank) {
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

    public static function setMemberRankSettings(array $settings, ?int $projectId = null): void
    {
        ShopSettingStore::setValue('member_rank_settings', $settings, 'json', $projectId);
    }

    public static function defaultMemberRankSettings(): array
    {
        return [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => true, 'card_color' => '#cd7f32', 'order' => 1, 'point_earn_rate' => null, 'stamp_earn_per_amount' => null, 'smaregi_rank_id' => null],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 1000, 'is_active' => true, 'card_color' => '#c0c0c0', 'order' => 2, 'point_earn_rate' => null, 'stamp_earn_per_amount' => null, 'smaregi_rank_id' => null],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 5000, 'is_active' => true, 'card_color' => '#ffd700', 'order' => 3, 'point_earn_rate' => null, 'stamp_earn_per_amount' => null, 'smaregi_rank_id' => null],
            ['key' => 'platinum', 'label' => 'プラチナ', 'min_points' => 10000, 'is_active' => true, 'card_color' => '#e5e4e2', 'order' => 4, 'point_earn_rate' => null, 'stamp_earn_per_amount' => null, 'smaregi_rank_id' => null],
        ];
    }
}
