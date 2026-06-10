<?php

namespace App\UseCases\Order\Data;

use App\Models\User;

class OrderStoreInput
{
    /** @param array<string, string|int|float|bool|array|null> $validated */
    public function __construct(
        public User $user,
        public array $validated,
    ) {}
}
