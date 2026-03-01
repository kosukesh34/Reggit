<?php

namespace App\UseCases\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentIntentStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\UseCases\Payment\Data\PaymentConfirmOutput;

class PaymentConfirmUseCase
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(string $paymentIntentId, int $orderId): PaymentConfirmOutput
    {
        $payment = $this->gateway->retrievePaymentIntent($paymentIntentId);
        $status = $payment['status'] ?? null;

        if ($status !== PaymentIntentStatus::SUCCEEDED) {
            return new PaymentConfirmOutput(false, '決済が完了していません', $status, null);
        }

        $order = Order::findOrFail($orderId);
        $order->update([
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
        ]);

        return new PaymentConfirmOutput(true, '決済が完了しました', $status, $order);
    }
}
