<?php

namespace App\UseCases\Payment;

use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\UseCases\Payment\Data\PaymentCheckoutOutput;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class PaymentCreateCheckoutUseCase
{
    public function __construct(
        private PaymentGateway $gateway,
        private OrderRepository $orderRepository,
    ) {}

    public function handle(int $orderId, int $projectId, int $userId): PaymentCheckoutOutput
    {
        $order = $this->orderRepository->findById($orderId, $projectId);
        if ($order === null) {
            throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
        }
        if ($order->userId() !== $userId) {
            throw new InvalidArgumentException('注文の所有者と一致しません');
        }
        if ($order->orderType() !== OrderType::MOBILE || $order->paymentMethod() !== PaymentMethod::STRIPE) {
            throw new InvalidArgumentException('カード払いのモバイル注文のみ決済できます');
        }
        if ($order->totalAmount() < 1) {
            throw new InvalidArgumentException('決済金額が不正です');
        }

        $returnBase = rtrim((string) config('app.url'), '/').'/mobile/payment/return';
        $successUrl = $returnBase.'?status=success&order_id='.$orderId.'&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $returnBase.'?status=cancel&order_id='.$orderId;
        $checkout = $this->gateway->createCheckoutSession(
            $order->totalAmount(),
            $orderId,
            $successUrl,
            $cancelUrl,
        );

        $url = trim((string) ($checkout['url'] ?? ''));
        if ($url === '') {
            throw new \RuntimeException('決済画面を作成できませんでした');
        }

        return new PaymentCheckoutOutput($url, (string) ($checkout['id'] ?? ''));
    }
}
