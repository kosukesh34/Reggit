<?php

namespace App\UseCases\Payment;

use App\UseCases\Payment\Dto\PaymentIntentDto;

class PaymentCreateIntentUseCase
{
    public function __construct(private PaymentGateway $gateway)
    {
    }

    public function handle(float $amount, ?int $orderId): PaymentIntentDto
    {
        $result = $this->gateway->createPaymentIntent((int) ($amount * 100), $orderId);
        return new PaymentIntentDto($result['client_secret'], $result['id']);
    }
}
