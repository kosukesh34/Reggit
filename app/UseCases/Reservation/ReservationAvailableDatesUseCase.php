<?php

namespace App\UseCases\Reservation;

use App\UseCases\Reservation\Data\ReservationAvailability;

class ReservationAvailableDatesUseCase
{
    public function __construct(private ReservationAvailabilityService $availabilityService) {}

    public function handle(?int $excludeReservationId, int $numberOfPeople = 1): ReservationAvailability
    {
        return $this->availabilityService->getAvailableDates($excludeReservationId, $numberOfPeople);
    }
}
