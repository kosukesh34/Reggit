<?php

namespace App\UseCases\Payment\Data;

/** 決済 Intent 作成の出力。不変。 */
class PaymentIntentOutput
{
    public function __construct(
        public string $clientSecret,
        public string $paymentIntentId,
        public ?string $publishableKey = null,
    ) {}

    /** @return array{clientSecret: string, paymentIntentId: string, publishableKey: string|null} */
    public function toArray(): array
    {
        return [
            'clientSecret' => $this->clientSecret,
            'paymentIntentId' => $this->paymentIntentId,
            'publishableKey' => $this->publishableKey,
        ];
    }
}
