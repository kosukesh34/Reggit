<?php

namespace App\UseCases\Order;

use App\Models\Order;
use App\Services\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class OrderIndexUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request, int $perPage): LengthAwarePaginator
    {
        $user = $this->userService->getOrCreateUser($request);
        return Order::where('user_id', $user->id)
            ->with('items.product')
            ->latest()
            ->paginate($perPage);
    }
}
