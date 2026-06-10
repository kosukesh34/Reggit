<?php

namespace App\UseCases\Order;

use App\Models\Order;
use App\UseCases\Order\Data\OrderShowInput;

class OrderShowUseCase
{
    public function handle(OrderShowInput $input): Order
    {
        return Order::where('user_id', $input->user->id)
            ->with('items.product')
            ->findOrFail($input->orderId);
    }
}
