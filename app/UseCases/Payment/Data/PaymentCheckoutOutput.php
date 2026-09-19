<?php

namespace App\UseCases\Payment\Data;

class PaymentCheckoutOutput
{
    public function __construct(
        public string $checkoutUrl,
        public string $sessionId,
    ) {}

    /** @return array{checkout_url: string, session_id: string} */
    public function toArray(): array
    {
        return [
            'checkout_url' => $this->checkoutUrl,
            'session_id' => $this->sessionId,
        ];
    }
}
