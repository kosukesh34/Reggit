<?php

namespace App\UseCases\Reservation\Data;

/**
 * 予約可能日取得の結果。不変。
 *
 * @param  array<int, array{date: string, day_of_week: int, display: string, time_slots: array<int, string>}>  $availableDates
 */
class ReservationAvailability
{
    public function __construct(
        public array $availableDates,
        public string $startDate,
        public string $endDate,
        public array $courses = [],
        public int $maxPartySize = 10,
        /** 会員が変更・キャンセルできるのは予約時刻の何時間前までか */
        public int $changeDeadlineHours = 0,
    ) {}
}
