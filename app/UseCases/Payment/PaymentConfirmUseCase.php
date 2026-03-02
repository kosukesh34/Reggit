<?php

namespace App\UseCases\Payment;

use App\Enums\OrderType;
use App\Enums\OrderStatus;
use App\Enums\PaymentIntentStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\UseCases\Payment\Data\PaymentConfirmOutput;

class PaymentConfirmUseCase
{
    private PaymentGateway $gateway;

    public function __construct(PaymentGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function handle(string $paymentIntentId, int $orderId): PaymentConfirmOutput
    {
        $payment = $this->gateway->retrievePaymentIntent($paymentIntentId);
        $status = $payment['status'] ?? null;

        if ($status !== PaymentIntentStatus::SUCCEEDED) {
            return new PaymentConfirmOutput(false, '決済が完了していません', $status, null);
        }

        $order = Order::findOrFail($orderId);
        if ($order->order_type !== OrderType::MOBILE) {
            return new PaymentConfirmOutput(false, '店内注文は決済確認の対象外です', (string) $status, null);
        }
        $order->update([
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
        ]);

        return new PaymentConfirmOutput(true, '決済が完了しました', $status, $order);
    }
}
