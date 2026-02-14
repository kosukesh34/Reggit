<?php

namespace App\UseCases\Payment\Dto;

class PaymentIntentDto
{
    public function __construct(
        public string $clientSecret,
        public string $paymentIntentId
    ) {
    }

    public function toArray(): array
    {
        return [
            'clientSecret' => $this->clientSecret,
            'paymentIntentId' => $this->paymentIntentId,
        ];
    }
}
