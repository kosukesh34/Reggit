<?php

namespace App\UseCases\Order;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\Repositories\OrderRepository;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\UseCases\Payment\PaymentGateway;
use App\UseCases\Payment\ProjectPaymentGatewayResolver;
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
            $order = Order::with('items.product')->findOrFail($orderId);
            $session = $order->payment_session;
            if ($session !== null) {
                $sessionId = $session['result']['id'] ?? null;
                if (! is_string($sessionId) || $sessionId === '') {
                    // The creator releases the row lock while contacting Stripe.
                    throw new OrderOperationException('決済画面を作成中です。しばらくしてから再度お試しください');
                }
                $gateway = app(ProjectPaymentGatewayResolver::class)->forOrder($order, app(PaymentGateway::class));
                $gateway->cancelUnpaidSession((string) ($session['channel'] ?? ''), $sessionId);
            }
            $this->orderRepository->save($aggregate);

            $order = Order::with('items.product')->findOrFail($orderId);
            $this->orderService->handleOrderCancellation($order);

            return $order->refresh();
        });

    }
}
