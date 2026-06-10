<?php

namespace App\UseCases\Reservation;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ShopSetting;
use App\UseCases\Reservation\Data\ReservationAvailability;
use App\Util\DateUtil;
use Carbon\Carbon;

class ReservationAvailabilityService
{
    private const DATE_FORMAT = 'Y-m-d';

    private const TIME_FORMAT = 'H:i';

    private const ACTIVE_STATUSES = [
        ReservationStatus::PENDING,
        ReservationStatus::CONFIRMED,
    ];

    public function getAvailableDates(?int $excludeReservationId): ReservationAvailability
    {
        $timeSlots = ShopSetting::getReservationTimeSlots();
        $advanceDays = ShopSetting::getAdvanceBookingDays();
        $reservationCapacity = ShopSetting::getReservationCapacityPerSlot();

        $availableDates = [];
        $today = now();
        $startDate = $today->copy()->addDay()->startOfDay();
        $endDate = $today->copy()->addDays($advanceDays)->endOfDay();
        $slotCounts = $this->getSlotCounts($startDate, $endDate, $excludeReservationId);

        for ($i = 1; $i <= $advanceDays; $i++) {
            $date = $today->copy()->addDays($i);
            $dateStr = $date->format(self::DATE_FORMAT);
            if (! ShopSetting::isDateAvailable($dateStr)) {
                continue;
            }

            $availableSlots = [];
            foreach ($timeSlots as $slot) {
                $slotCount = $slotCounts[$dateStr][$slot] ?? 0;
                if ($slotCount < $reservationCapacity) {
                    $availableSlots[] = $slot;
                }
            }

            if (count($availableSlots) === 0) {
                continue;
            }

            $availableDates[] = [
                'date' => $dateStr,
                'day_of_week' => $date->format('w'),
                'display' => $date->format('Y年m月d日').'('.$this->getDayOfWeekName($date->dayOfWeek).')',
                'time_slots' => $availableSlots,
            ];
        }

        return new ReservationAvailability(
            $availableDates,
            $startDate->format(self::DATE_FORMAT),
            $endDate->format(self::DATE_FORMAT)
        );
    }

    public function getAvailabilityError(Carbon $reservedAt, ?int $excludeReservationId): ?string
    {
        $advanceDays = ShopSetting::getAdvanceBookingDays();
        $startDate = now()->addDay()->startOfDay();
        $endDate = now()->addDays($advanceDays)->endOfDay();

        if ($reservedAt->lt($startDate) || $reservedAt->gt($endDate)) {
            return '予約可能な期間外です';
        }

        $dateStr = $reservedAt->format(self::DATE_FORMAT);
        if (! ShopSetting::isDateAvailable($dateStr)) {
            return '選択した日は予約できません';
        }

        $timeStr = $reservedAt->format(self::TIME_FORMAT);
        $timeSlots = ShopSetting::getReservationTimeSlots();
        if (! in_array($timeStr, $timeSlots, true)) {
            return '選択した時間は予約できません';
        }

        $reservationCapacity = ShopSetting::getReservationCapacityPerSlot();
        if ($this->isSlotAvailable($reservedAt, $reservationCapacity, $excludeReservationId) === false) {
            return '選択した時間帯は満席です';
        }

        return null;
    }

    private function isSlotAvailable(Carbon $reservedAt, int $reservationCapacity, ?int $excludeReservationId): bool
    {
        $slotStart = $reservedAt->copy()->startOfMinute();
        $slotEnd = $reservedAt->copy()->endOfMinute();
        $query = Reservation::whereBetween('reserved_at', [$slotStart, $slotEnd])
            ->whereIn('status', self::ACTIVE_STATUSES);
        if ($excludeReservationId !== null) {
            $query->where('id', '!=', $excludeReservationId);
        }
        $reservedCount = $query->count();

        return $reservedCount < $reservationCapacity;
    }

    private function getDayOfWeekName(int $dayOfWeek): string
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];

        return $days[$dayOfWeek] ?? '';
    }

    private function getSlotCounts(Carbon $startDate, Carbon $endDate, ?int $excludeReservationId): array
    {
        $query = Reservation::whereBetween('reserved_at', [$startDate, $endDate])
            ->whereIn('status', self::ACTIVE_STATUSES);
        $reservations = $query->get(['id', 'reserved_at']);

        $slotCounts = [];
        foreach ($reservations as $reservation) {
            if ($excludeReservationId !== null && (int) $reservation->id === $excludeReservationId) {
                continue;
            }
            $reservedAt = $reservation->reserved_at instanceof Carbon
                ? $reservation->reserved_at
                : DateUtil::nullableCarbon($reservation->reserved_at);
            $dateKey = $reservedAt->format(self::DATE_FORMAT);
            $timeKey = $reservedAt->format(self::TIME_FORMAT);
            if (! isset($slotCounts[$dateKey])) {
                $slotCounts[$dateKey] = [];
            }
            if (! isset($slotCounts[$dateKey][$timeKey])) {
                $slotCounts[$dateKey][$timeKey] = 0;
            }
            $slotCounts[$dateKey][$timeKey] += 1;
        }

        return $slotCounts;
    }
}
