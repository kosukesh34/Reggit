<?php

namespace App\UseCases\Reservation\Data;

use App\Models\User;

class ReservationUpdateInput
{
    /** @param array<string, string|int|float|bool|array|null> $validated */
    public function __construct(
        public User $user,
        public int $reservationId,
        public array $validated,
    ) {}
}
