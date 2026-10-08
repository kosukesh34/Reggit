<?php

namespace App\UseCases\Order;

use App\Domain\Order\Repositories\OrderRepository;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Util\AdminContext;
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
        return DB::transaction(function () use ($user, $orderId) {
            $aggregate = $this->orderRepository->findByIdAndUserIdForUpdate(
                $orderId,
                (int) $user->id,
                (int) (AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null)),
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

            return $order->refresh();
        });

    }
}
