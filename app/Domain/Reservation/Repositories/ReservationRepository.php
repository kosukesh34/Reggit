<?php

namespace App\Domain\Reservation\Repositories;

use App\Domain\Reservation\Aggregates\Reservation;
use App\Domain\Reservation\ValueObjects\PendingReservation;

interface ReservationRepository
{
    public function findByIdForUpdate(int $reservationId, int $projectId, ?int $userId = null): ?Reservation;

    public function findById(int $reservationId, int $projectId): ?Reservation;

    public function findByIdAndUserId(int $reservationId, int $userId, int $projectId): ?Reservation;

    public function findByIdAndUserIdOrFail(int $reservationId, int $userId, int $projectId): Reservation;

    public function persistPending(PendingReservation $pending): int;

    /**
     * @param  bool  $countPeople  true なら人数の合計、false なら件数
     * @return array<string, array<string, int>> date => time => count
     */
    public function getActiveSlotCounts(
        int $projectId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $excludeReservationId,
        bool $countPeople = false,
    ): array;

    /** @param  bool  $countPeople  true なら人数の合計、false なら件数 */
    public function countActiveInSlot(
        int $projectId,
        \DateTimeInterface $slotStart,
        \DateTimeInterface $slotEnd,
        ?int $excludeReservationId,
        bool $countPeople = false,
    ): int;

    public function save(Reservation $reservation): void;
}
