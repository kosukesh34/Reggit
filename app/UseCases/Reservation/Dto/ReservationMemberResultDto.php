<?php

namespace App\UseCases\Reservation\Dto;

use Illuminate\Support\Collection;

class ReservationMemberResultDto
{
    /** @param Collection<int, \App\Models\Reservation> $reservations */
    public function __construct(
        public bool $requiresMembership,
        public Collection $reservations
    ) {
    }
}
