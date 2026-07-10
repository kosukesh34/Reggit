<?php

namespace App\Domain\Reservation\Policies;

final class ReservationSlotPolicy
{
    public const DATE_FORMAT = 'Y-m-d';

    public const TIME_FORMAT = 'H:i';

    /**
     * @param  list<string>  $timeSlots
     */
    public function availabilityError(
        \DateTimeInterface $reservedAt,
        \DateTimeInterface $now,
        int $advanceDays,
        array $timeSlots,
        bool $isDateAvailable,
        int $capacity,
        int $currentSlotCount,
    ): ?string {
        $start = $this->startOfDay($this->addDays($now, 1));
        $end = $this->endOfDay($this->addDays($now, $advanceDays));

        if ($reservedAt < $start || $reservedAt > $end) {
            return '予約可能な期間外です';
        }

        if ($isDateAvailable === false) {
            return '選択した日は予約できません';
        }

        $timeStr = $reservedAt->format(self::TIME_FORMAT);
        if (! in_array($timeStr, $timeSlots, true)) {
            return '選択した時間は予約できません';
        }

        if ($currentSlotCount >= $capacity) {
            return '選択した時間帯は満席です';
        }

        return null;
    }

    /**
     * @param  list<string>  $timeSlots
     * @param  array<string, bool>  $dateAvailability  date => isAvailable
     * @param  array<string, array<string, int>>  $slotCounts  date => time => count
     * @return list<array{date: string, day_of_week: string, display: string, time_slots: list<string>}>
     */
    public function buildAvailableDates(
        \DateTimeInterface $today,
        int $advanceDays,
        array $timeSlots,
        array $dateAvailability,
        array $slotCounts,
        int $capacity,
    ): array {
        $availableDates = [];

        for ($i = 1; $i <= $advanceDays; $i++) {
            $date = $this->addDays($today, $i);
            $dateStr = $date->format(self::DATE_FORMAT);
            if (($dateAvailability[$dateStr] ?? false) === false) {
                continue;
            }

            $availableSlots = [];
            foreach ($timeSlots as $slot) {
                $slotCount = $slotCounts[$dateStr][$slot] ?? 0;
                if ($slotCount < $capacity) {
                    $availableSlots[] = $slot;
                }
            }

            if ($availableSlots === []) {
                continue;
            }

            $dayOfWeek = (int) $date->format('w');
            $availableDates[] = [
                'date' => $dateStr,
                'day_of_week' => (string) $dayOfWeek,
                'display' => $date->format('Y年m月d日').'('.$this->dayOfWeekName($dayOfWeek).')',
                'time_slots' => $availableSlots,
            ];
        }

        return $availableDates;
    }

    public function dayOfWeekName(int $dayOfWeek): string
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];

        return $days[$dayOfWeek] ?? '';
    }

    private function addDays(\DateTimeInterface $date, int $days): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($date)->modify('+'.$days.' days');
    }

    private function startOfDay(\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
    }

    private function endOfDay(\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($date)->setTime(23, 59, 59);
    }
}
