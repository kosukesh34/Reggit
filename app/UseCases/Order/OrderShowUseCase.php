<?php

namespace App\UseCases\Order;

use App\Models\Order;
use App\Services\UserService;
use Illuminate\Http\Request;

class OrderShowUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request, int $id): Order
    {
        $user = $this->userService->getOrCreateUser($request);
        return Order::where('user_id', $user->id)
            ->with('items.product')
            ->findOrFail($id);
    }
}
