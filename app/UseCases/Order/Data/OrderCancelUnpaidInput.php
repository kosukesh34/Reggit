<?php

namespace App\UseCases\Order\Data;

use App\Models\User;

class OrderCancelUnpaidInput
{
    public function __construct(
        public User $user,
        public int $orderId,
    ) {}
}
