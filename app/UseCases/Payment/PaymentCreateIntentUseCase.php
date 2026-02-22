<?php

namespace App\UseCases\Payment;

use App\UseCases\Payment\Data\PaymentIntentResult;

class PaymentCreateIntentUseCase
{
    public function __construct(private PaymentGateway $gateway)
    {
    }

    public function handle(float $amount, ?int $orderId): PaymentIntentResult
    {
        $result = $this->gateway->createPaymentIntent((int) ($amount * 100), $orderId);
        return new PaymentIntentResult($result['client_secret'], $result['id']);
    }
}
