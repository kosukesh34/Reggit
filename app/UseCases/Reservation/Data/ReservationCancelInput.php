<?php

namespace App\UseCases\Reservation\Data;

use App\Models\User;

class ReservationCancelInput
{
    public function __construct(
        public User $user,
        public int $reservationId,
    ) {}
}
