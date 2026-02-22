<?php

namespace App\UseCases\Payment\Data;

/** 決済 Intent 作成の結果。不変。 */
class PaymentIntentResult
{
    public function __construct(
        public string $clientSecret,
        public string $paymentIntentId
    ) {
    }

    /** @return array{clientSecret: string, paymentIntentId: string} */
    public function toArray(): array
    {
        return [
            'clientSecret' => $this->clientSecret,
            'paymentIntentId' => $this->paymentIntentId,
        ];
    }
}
