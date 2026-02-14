<?php

namespace App\UseCases\Reservation\Dto;

class ReservationAvailabilityDto
{
    /** @param array<int, array<string, mixed>> $availableDates */
    public function __construct(
        public array $availableDates,
        public string $startDate,
        public string $endDate
    ) {
    }
}
