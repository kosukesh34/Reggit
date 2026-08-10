<?php

namespace App\UseCases\Payment;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\UseCases\Payment\Data\PaymentIntentOutput;
use InvalidArgumentException;

class PaymentCreateIntentUseCase
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(Order $order, int $userId): PaymentIntentOutput
    {
        if ((int) $order->user_id !== $userId) {
            throw new InvalidArgumentException('注文の所有者と一致しません');
        }

        if ($order->order_type !== OrderType::MOBILE || $order->payment_method !== PaymentMethod::STRIPE) {
            throw new InvalidArgumentException('モバイル注文（注文時決済）のみ決済を作成できます');
        }

        // JPY は zero-decimal。Stripe には円単位の整数を渡す（*100 しない）
        $amount = (int) round((float) $order->total_amount);
        if ($amount < 1) {
            throw new InvalidArgumentException('決済金額が不正です');
        }

        $result = $this->gateway->createPaymentIntent($amount, (int) $order->id);

        return new PaymentIntentOutput($result['client_secret'], $result['id']);
    }
}
