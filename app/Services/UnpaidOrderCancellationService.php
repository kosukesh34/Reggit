<?php

namespace App\Services;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\Repositories\OrderRepository;
use App\Models\Order;
use App\Services\Payment\UnpaidPaymentSessionCanceller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class UnpaidOrderCancellationService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderCancellationReverser $cancellations,
        private UnpaidPaymentSessionCanceller $paymentSessions,
    ) {}

    public function cancel(int $orderId, int $projectId, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($userId, $projectId, $orderId) {
            $aggregate = $userId === null
                ? $this->orderRepository->findByIdForUpdate($orderId, $projectId)
                : $this->orderRepository->findByIdAndUserIdForUpdate($orderId, $userId, $projectId);
            if ($aggregate === null) {
                throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
            }

            if ($aggregate->isCancelled()) {
                return Order::with('items.product')->findOrFail($orderId);
            }

            $aggregate->cancelUnpaidStripeMobile();
            $order = Order::with('items.product')->findOrFail($orderId);
            $session = $order->payment_session;
            if ($session !== null) {
                $sessionId = $session['result']['id'] ?? null;
                if (! is_string($sessionId) || $sessionId === '') {
                    // The creator releases the row lock while contacting Stripe.
                    throw new OrderOperationException('決済画面を作成中です。しばらくしてから再度お試しください');
                }
                $this->paymentSessions->cancel($order, (string) ($session['channel'] ?? ''), $sessionId);
            }
            $this->orderRepository->save($aggregate);

            $order = Order::with('items.product')->findOrFail($orderId);
            $this->cancellations->handleOrderCancellation($order);

            return $order->refresh();
        });

    }
}
