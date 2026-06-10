<?php

namespace App\UseCases\Reservation\Data;

use App\Models\User;

class ReservationStoreInput
{
    /** @param array<string, string|int|float|bool|array|null> $validated */
    public function __construct(
        public User $user,
        public array $validated,
    ) {}
}
