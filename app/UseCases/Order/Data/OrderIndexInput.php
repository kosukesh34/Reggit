<?php

namespace App\UseCases\Order\Data;

use App\Models\User;

class OrderIndexInput
{
    public function __construct(
        public User $user,
        public int $perPage = 20,
    ) {}
}
