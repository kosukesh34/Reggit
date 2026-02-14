<?php

namespace App\UseCases\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentIntentStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\UseCases\Payment\Dto\PaymentConfirmResultDto;

class PaymentConfirmUseCase
{
    public function __construct(private PaymentGateway $gateway)
    {
    }

    public function handle(string $paymentIntentId, int $orderId): PaymentConfirmResultDto
    {
        $payment = $this->gateway->retrievePaymentIntent($paymentIntentId);
        $status = $payment['status'] ?? null;

        if ($status !== PaymentIntentStatus::SUCCEEDED) {
            return new PaymentConfirmResultDto(false, '決済が完了していません', $status, null);
        }

        $order = Order::findOrFail($orderId);
        $order->update([
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
        ]);

        return new PaymentConfirmResultDto(true, '決済が完了しました', $status, $order);
    }
}
