<?php

namespace App\Domain\Coupon\Policies;

use App\Enums\CouponDeliveryType;

/**
 * クーポン配信セグメントの条件正規化・判定（クエリは Repository / Service 側）。
 */
final class CouponDeliverySegmentPolicy
{
    public function isKnownDeliveryType(string $deliveryType): bool
    {
        return array_key_exists($deliveryType, CouponDeliveryType::getValues());
    }

    public function requiresPersonalMembers(string $deliveryType): bool
    {
        return in_array($deliveryType, CouponDeliveryType::personalValues(), true);
    }

    public function isAllMembersDelivery(string $deliveryType): bool
    {
        return CouponDeliveryType::isAllMembersDelivery($deliveryType);
    }

    public function normalizeBirthdayMonth(int $month, int $fallbackMonth): int
    {
        if ($month < 1 || $month > 12) {
            return $fallbackMonth;
        }

        return $month;
    }

    public function normalizePositiveDays(int $days, int $default): int
    {
        return max(1, $days > 0 ? $days : $default);
    }

    /**
     * @param  array{
     *     segment_condition?: string,
     *     segment_period_days?: int|string,
     *     segment_min_amount?: float|int|string,
     *     birthday_month?: int|string
     * }  $params
     * @return array{
     *     period_days?: int,
     *     condition?: string,
     *     min_total_amount?: float|null,
     *     birthday_month?: int
     * }|null
     */
    public function buildSegmentCriteria(string $deliveryType, array $params, int $fallbackBirthdayMonth): ?array
    {
        if (in_array($deliveryType, CouponDeliveryType::segmentValues(), true)) {
            $condition = (string) ($params['segment_condition'] ?? 'min_amount');

            return [
                'period_days' => (int) ($params['segment_period_days'] ?? 30),
                'condition' => $condition,
                'min_total_amount' => $condition === 'min_amount' ? (float) ($params['segment_min_amount'] ?? 0) : null,
            ];
        }

        if (in_array($deliveryType, CouponDeliveryType::birthdayValues(), true)) {
            return [
                'birthday_month' => $this->normalizeBirthdayMonth(
                    (int) ($params['birthday_month'] ?? $fallbackBirthdayMonth),
                    $fallbackBirthdayMonth,
                ),
            ];
        }

        if (in_array($deliveryType, CouponDeliveryType::newMemberValues(), true)) {
            return [
                'new_member_days' => $this->normalizePositiveDays((int) ($params['new_member_days'] ?? 30), 30),
            ];
        }

        if (in_array($deliveryType, CouponDeliveryType::inactiveValues(), true)) {
            return [
                'inactive_days' => $this->normalizePositiveDays((int) ($params['inactive_days'] ?? 90), 90),
            ];
        }

        return null;
    }

    public function isNoOrdersSalesCondition(string $condition): bool
    {
        return $condition === 'no_orders';
    }
}
