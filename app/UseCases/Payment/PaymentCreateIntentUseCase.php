<?php

namespace App\UseCases\Payment;

use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\UseCases\Payment\Data\PaymentIntentOutput;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class PaymentCreateIntentUseCase
{
    public function __construct(
        private PaymentGateway $gateway,
        private OrderRepository $orderRepository,
    ) {}

    public function handle(int $orderId, int $projectId, int $userId): PaymentIntentOutput
    {
        $order = $this->orderRepository->findById($orderId, $projectId);
        if ($order === null) {
            throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
        }

        if ($order->userId() !== $userId) {
            throw new InvalidArgumentException('注文の所有者と一致しません');
        }

        if ($order->orderType() !== OrderType::MOBILE || $order->paymentMethod() !== PaymentMethod::STRIPE) {
            throw new InvalidArgumentException('モバイル注文（注文時決済）のみ決済を作成できます');
        }

        // JPY は zero-decimal。Stripe には円単位の整数を渡す（*100 しない）
        $amount = $order->totalAmount();
        if ($amount < 1) {
            throw new InvalidArgumentException('決済金額が不正です');
        }

        $result = $this->gateway->createPaymentIntent($amount, $order->id()->value());

        return new PaymentIntentOutput($result['client_secret'], $result['id']);
    }
}
