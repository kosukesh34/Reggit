<?php

namespace App\UseCases\Order;

use App\Domain\Order\Repositories\OrderRepository;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class OrderCancelUnpaidUseCase
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderService $orderService,
    ) {}

    public function handle(User $user, int $orderId): Order
    {
        /** @var array{0: Order, 1: bool} $result */
        $result = DB::transaction(function () use ($user, $orderId) {
            $aggregate = $this->orderRepository->findByIdAndUserIdForUpdate(
                $orderId,
                (int) $user->id,
                (int) ($user->project_id ?? 0),
            );
            if ($aggregate === null) {
                throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
            }

            if ($aggregate->isCancelled()) {
                return [Order::with('items.product')->findOrFail($orderId), false];
            }

            $aggregate->cancelUnpaidStripeMobile();
            $this->orderRepository->save($aggregate);

            return [Order::with('items.product')->findOrFail($orderId), true];
        });

        [$order, $shouldHandleCancellation] = $result;
        if ($shouldHandleCancellation) {
            $this->orderService->handleOrderCancellation($order);
        }

        return $order;
    }
}
