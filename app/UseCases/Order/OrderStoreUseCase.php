<?php

namespace App\UseCases\Order;

use App\Services\OrderService;
use App\Services\UserService;
use App\UseCases\Order\Data\OrderFactory;
use App\UseCases\Order\Data\OrderResult;
use App\Enums\OrderType;
use App\Util\StrHelper;
use Illuminate\Http\Request;

class OrderStoreUseCase
{
    public function __construct(
        private OrderService $orderService,
        private UserService $userService,
        private OrderFactory $orderFactory
    ) {
    }

    /** @param array<string, string|int|float|bool|array|null> $validated 検証済み注文データ（items, payment_method, points_used 等） */
    public function handle(Request $request, array $validated): OrderResult
    {
        if (StrHelper::isEmptyStringOrNull($validated['order_type'] ?? null)) {
            $validated['order_type'] = OrderType::MOBILE;
        }
        $user = $this->userService->getOrCreateUser($request);
        $order = $this->orderService->createOrder($validated, $user);
        return $this->orderFactory->create($order);
    }
}
