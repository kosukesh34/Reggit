<?php

namespace App\UseCases\Order;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderIndexUseCase
{
    public function handle(User $user, int $perPage): LengthAwarePaginator
    {
        return Order::where('user_id', $user->id)
            ->with('items.product')
            ->latest()
            ->paginate($perPage);
    }
}
