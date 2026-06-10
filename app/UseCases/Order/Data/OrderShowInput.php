<?php

namespace App\UseCases\Order\Data;

use App\Models\User;

class OrderShowInput
{
    public function __construct(
        public User $user,
        public int $orderId,
    ) {}
}
