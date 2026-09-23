<?php

namespace App\UseCases\Payment;

use App\UseCases\Payment\Data\PaymentIntentOutput;

class PaymentCreateIntentUseCase
{
    public function __construct(
        private OrderPaymentSessionService $sessions,
    ) {}

    public function handle(int $orderId, int $projectId, int $userId): PaymentIntentOutput
    {
        $result = $this->sessions->create($orderId, $projectId, $userId, 'intent');

        return new PaymentIntentOutput($result['client_secret'], $result['id']);
    }
}
