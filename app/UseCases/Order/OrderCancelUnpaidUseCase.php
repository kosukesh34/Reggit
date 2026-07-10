<?php

namespace App\UseCases\Order;

use App\Domain\Order\Repositories\OrderRepository;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderCancelUnpaidUseCase
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderService $orderService,
    ) {}

    public function handle(User $user, int $orderId): Order
    {
        $aggregate = $this->orderRepository->findByIdAndUserId(
            $orderId,
            (int) $user->id,
            (int) ($user->project_id ?? 0),
        );
        if ($aggregate === null) {
            throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
        }

        if ($aggregate->isCancelled()) {
            return Order::with('items.product')->findOrFail($orderId);
        }

        $aggregate->cancelUnpaidStripeMobile();
        $this->orderRepository->save($aggregate);

        $order = Order::with('items.product')->findOrFail($orderId);
        $this->orderService->handleOrderCancellation($order);

        return $order;
    }
}
