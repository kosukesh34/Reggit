<?php

namespace App\UseCases\Order;

use App\Models\Order;
use App\Models\User;

class OrderShowUseCase
{
    public function handle(User $user, int $id): Order
    {
        return Order::where('user_id', $user->id)
            ->with('items.product')
            ->findOrFail($id);
    }
}
