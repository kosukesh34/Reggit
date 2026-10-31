<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

/** 営業時間・予約枠・順番待ちの設定。店舗ごとの上書きは ShopSettingStore が解決する。 */
class BusinessSettingStore
{
    public function __construct(
        private ShopSettingStore $values,
    ) {}

    private const DEFAULT_RESERVATION_CAPACITY = 20;

    public function getBusinessHours(?int $projectId = null): array
    {
        return [
            'start' => $this->values->getValue('business_hours_start', '10:00', $projectId),
            'end' => $this->values->getValue('business_hours_end', '22:00', $projectId),
        ];
    }

    public function getReservationTimeSlots(?int $projectId = null): array
    {
        return $this->values->getValue('reservation_time_slots', ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'], $projectId);
    }

    public function getClosedDays(?int $projectId = null): array
    {
        return $this->values->getValue('closed_days', [], $projectId);
    }

    public function getClosedDates(?int $projectId = null): array
    {
        return $this->values->getValue('closed_dates', [], $projectId);
    }

    public function getAdvanceBookingDays(?int $projectId = null): int
    {
        return $this->values->getValue('advance_booking_days', 30, $projectId);
    }

    public function getReservationCapacityPerSlot(?int $projectId = null): int
    {
        return $this->values->getValue('reservation_capacity_per_slot', self::DEFAULT_RESERVATION_CAPACITY, $projectId);
    }

    /** 順番待ち 1組あたりの平均対応時間（分）。予測待ち時間の計算に使用。 */
    public function getQueueAvgServeMinutes(?int $projectId = null): int
    {
        return $this->values->getValue('queue_avg_serve_minutes', 10, $projectId);
    }

    public function getQueueRequireMembership(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('queue_require_membership', false, $projectId);
    }

    public function getQueueChannelLine(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('queue_channel_line', true, $projectId);
    }

    public function getQueueChannelNative(?int $projectId = null): bool
    {
        return (bool) $this->values->getValue('queue_channel_native', false, $projectId);
    }

    public function isDateAvailable(string $date, ?int $projectId = null): bool
    {
        $dayOfWeek = (int) date('w', strtotime($date));
        $closedDays = $this->getClosedDays($projectId);

        if (in_array($dayOfWeek, $closedDays, true)) {
            return false;
        }

        $dateStr = date('Y-m-d', strtotime($date));
        $closedDates = $this->getClosedDates($projectId);

        if (in_array($dateStr, $closedDates, true)) {
            return false;
        }

        return true;
    }
}
