<?php

namespace App\UseCases\Reservation;

use App\UseCases\Reservation\Dto\ReservationAvailabilityDto;

class ReservationAvailableDatesUseCase
{
    public function __construct(private ReservationAvailabilityService $availabilityService)
    {
    }

    public function handle(?int $excludeReservationId): ReservationAvailabilityDto
    {
        return $this->availabilityService->getAvailableDates($excludeReservationId);
    }
}
