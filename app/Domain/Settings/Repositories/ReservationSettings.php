<?php

namespace App\Domain\Settings\Repositories;

interface ReservationSettings
{
    /** @return list<string> */
    public function getReservationTimeSlots(int $projectId): array;

    public function getAdvanceBookingDays(int $projectId): int;

    public function getReservationCapacityPerSlot(int $projectId): int;

    public function isDateAvailable(string $date, int $projectId): bool;
}
