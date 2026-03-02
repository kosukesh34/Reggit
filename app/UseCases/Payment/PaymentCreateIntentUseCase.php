<?php

namespace App\UseCases\Payment;

use App\UseCases\Payment\Data\PaymentIntentOutput;

class PaymentCreateIntentUseCase
{
    private PaymentGateway $gateway;

    public function __construct(PaymentGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function handle(float $amount, ?int $orderId): PaymentIntentOutput
    {
        $result = $this->gateway->createPaymentIntent((int) ($amount * 100), $orderId);
        return new PaymentIntentOutput($result['client_secret'], $result['id']);
    }
}
