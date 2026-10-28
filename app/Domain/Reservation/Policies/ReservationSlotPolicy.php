<?php

namespace App\Domain\Reservation\Policies;

use App\Domain\Reservation\ValueObjects\ReservationRules;

final class ReservationSlotPolicy
{
    public const DATE_FORMAT = 'Y-m-d';

    public const TIME_FORMAT = 'H:i';

    /**
     * @param  int  $usedSeats  この枠ですでに使われている量（件数か人数。ReservationRules::seatsFor と同じ単位）
     */
    public function availabilityError(
        \DateTimeInterface $reservedAt,
        \DateTimeInterface $now,
        ReservationRules $rules,
        bool $isDateAvailable,
        int $numberOfPeople,
        int $usedSeats,
    ): ?string {
        if ($numberOfPeople > $rules->maxPartySize) {
            return '1回の予約は'.$rules->maxPartySize.'名までです';
        }

        if ($reservedAt < $this->firstBookableDay($now, $rules) || $reservedAt > $this->lastBookableDay($now, $rules)) {
            return '予約可能な期間外です';
        }

        if ($isDateAvailable === false) {
            return '選択した日は予約できません';
        }

        if (! in_array($reservedAt->format(self::TIME_FORMAT), $rules->timeSlots, true)) {
            return '選択した時間は予約できません';
        }

        if (! $this->isBeforeLeadTime($reservedAt, $now, $rules)) {
            return 'この時間の予約受付は終了しました';
        }

        if ($usedSeats + $rules->seatsFor($numberOfPeople) > $rules->capacity) {
            return '選択した時間帯は満席です';
        }

        return null;
    }

    /**
     * @param  array<string, bool>  $dateAvailability  date => isAvailable
     * @param  array<string, array<string, int>>  $usedSeats  date => time => 使われている量
     * @return list<array{date: string, day_of_week: string, display: string, time_slots: list<string>}>
     */
    public function buildAvailableDates(
        \DateTimeInterface $now,
        ReservationRules $rules,
        array $dateAvailability,
        array $usedSeats,
        int $numberOfPeople,
    ): array {
        $availableDates = [];
        $seats = $rules->seatsFor($numberOfPeople);

        foreach ($this->bookableDates($now, $rules) as $date) {
            $dateStr = $date->format(self::DATE_FORMAT);
            if (($dateAvailability[$dateStr] ?? false) === false) {
                continue;
            }

            $availableSlots = [];
            foreach ($rules->timeSlots as $slot) {
                $slotStart = \DateTimeImmutable::createFromFormat('!'.self::DATE_FORMAT.' '.self::TIME_FORMAT, $dateStr.' '.$slot, $date->getTimezone());
                if ($slotStart === false || ! $this->isBeforeLeadTime($slotStart, $now, $rules)) {
                    continue;
                }
                if (($usedSeats[$dateStr][$slot] ?? 0) + $seats <= $rules->capacity) {
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

    /** @return list<\DateTimeImmutable> 受付期間の日付（休業日を含む） */
    public function bookableDates(\DateTimeInterface $now, ReservationRules $rules): array
    {
        $dates = [];
        for ($i = $rules->sameDay ? 0 : 1; $i <= $rules->advanceDays; $i++) {
            $dates[] = $this->startOfDay($this->addDays($now, $i));
        }

        return $dates;
    }

    public function firstBookableDay(\DateTimeInterface $now, ReservationRules $rules): \DateTimeImmutable
    {
        return $this->startOfDay($this->addDays($now, $rules->sameDay ? 0 : 1));
    }

    public function lastBookableDay(\DateTimeInterface $now, ReservationRules $rules): \DateTimeImmutable
    {
        return $this->addDays($now, $rules->advanceDays)->setTime(23, 59, 59);
    }

    /** 会員が変更・キャンセルできる最後の時刻 */
    public function changeDeadline(\DateTimeInterface $reservedAt, ReservationRules $rules): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($reservedAt)->modify('-'.$rules->changeDeadlineHours.' hours');
    }

    public function changeError(\DateTimeInterface $reservedAt, \DateTimeInterface $now, ReservationRules $rules): ?string
    {
        if ($now < $this->changeDeadline($reservedAt, $rules)) {
            return null;
        }

        return $rules->changeDeadlineHours > 0
            ? '予約の変更・キャンセルは予約時刻の'.$rules->changeDeadlineHours.'時間前までです。店舗へ直接ご連絡ください'
            : '予約時刻を過ぎたため変更・キャンセルできません';
    }

    public function dayOfWeekName(int $dayOfWeek): string
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];

        return $days[$dayOfWeek] ?? '';
    }

    private function isBeforeLeadTime(\DateTimeInterface $slotStart, \DateTimeInterface $now, ReservationRules $rules): bool
    {
        return \DateTimeImmutable::createFromInterface($slotStart)->modify('-'.$rules->leadMinutes.' minutes') > $now;
    }

    private function addDays(\DateTimeInterface $date, int $days): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($date)->modify('+'.$days.' days');
    }

    private function startOfDay(\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
    }
}
