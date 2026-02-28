<?php

namespace App\UseCases\Admin\Reservation\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 管理画面予約一覧 UseCase の出力。不変。
 *
 * @param array<string, int>|null $dateStatusCounts
 * @param array<int, array{time: string, count: int, capacity: int, is_full: bool}> $timeSlotSummary
 */
class AdminReservationIndexOutput
{
    public function __construct(
        public LengthAwarePaginator $reservations,
        public array $statusCounts,
        public ?string $selectedDate,
        public ?string $selectedDateLabel,
        public ?array $dateStatusCounts,
        public array $timeSlotSummary,
        public int $reservationCapacity
    ) {
    }
}
