<?php

namespace App\UseCases\Reservation\Dto;

use App\Models\Reservation;

class ReservationActionResultDto
{
    public function __construct(
        public bool $requiresMembership,
        public ?Reservation $reservation,
        public ?string $availabilityError
    ) {
    }
}
