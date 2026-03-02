<?php

namespace App\UseCases\Reservation;

use App\UseCases\Reservation\Data\ReservationAvailability;

class ReservationAvailableDatesUseCase
{
    public function __construct(private ReservationAvailabilityService $availabilityService) {}

    public function handle(?int $excludeReservationId): ReservationAvailability
    {
        return $this->availabilityService->getAvailableDates($excludeReservationId);
    }
}
