<?php

namespace App\UseCases\Order;

use App\Models\Order;
use App\UseCases\Order\Data\OrderIndexInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderIndexUseCase
{
    public function handle(OrderIndexInput $input): LengthAwarePaginator
    {
        return Order::where('user_id', $input->user->id)
            ->with('items.product')
            ->latest()
            ->paginate($input->perPage);
    }
}
