<?php

namespace App\UseCases\Order;

use App\Services\OrderService;
use App\Services\UserService;
use App\UseCases\Order\Dto\OrderDto;
use App\UseCases\Order\Dto\OrderDtoFactory;
use App\Enums\OrderType;
use App\Util\StrHelper;
use Illuminate\Http\Request;

class OrderStoreUseCase
{
    public function __construct(
        private OrderService $orderService,
        private UserService $userService,
        private OrderDtoFactory $dtoFactory
    ) {
    }

    public function handle(Request $request, array $validated): OrderDto
    {
        if (StrHelper::isEmptyStringOrNull($validated['order_type'] ?? null)) {
            $validated['order_type'] = OrderType::MOBILE;
        }
        $user = $this->userService->getOrCreateUser($request);
        $order = $this->orderService->createOrder($validated, $user);
        return $this->dtoFactory->create($order);
    }
}
