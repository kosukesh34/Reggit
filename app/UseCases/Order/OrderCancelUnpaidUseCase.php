<?php

namespace App\UseCases\Order;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\UserService;
use Illuminate\Http\Request;

class OrderCancelUnpaidUseCase
{
    private UserService $userService;

    private OrderService $orderService;

    public function __construct(UserService $userService, OrderService $orderService)
    {
        $this->userService = $userService;
        $this->orderService = $orderService;
    }

    public function handle(Request $request, int $orderId): Order
    {
        $user = $this->userService->getOrCreateUser($request);
        $order = Order::where('user_id', $user->id)->findOrFail($orderId);

        if ($order->order_type !== OrderType::MOBILE || $order->payment_method !== PaymentMethod::STRIPE) {
            throw new \Exception('この注文は未決済キャンセル対象ではありません');
        }

        if (in_array((string) $order->status, [OrderStatus::CONFIRMED, OrderStatus::PREPARING, OrderStatus::READY, OrderStatus::COMPLETED], true)) {
            throw new \Exception('決済済みまたは進行中の注文はキャンセルできません');
        }

        if ($order->status === OrderStatus::CANCELLED) {
            return $order->load('items.product');
        }

        $order->status = OrderStatus::CANCELLED;
        $order->save();
        $this->orderService->handleOrderCancellation($order);

        return $order->load('items.product');
    }
}
