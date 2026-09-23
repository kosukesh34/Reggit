<?php

namespace App\UseCases\Payment;

use App\UseCases\Payment\Data\PaymentCheckoutOutput;

class PaymentCreateCheckoutUseCase
{
    public function __construct(
        private OrderPaymentSessionService $sessions,
    ) {}

    public function handle(int $orderId, int $projectId, int $userId): PaymentCheckoutOutput
    {
        $result = $this->sessions->create($orderId, $projectId, $userId, 'checkout');

        return new PaymentCheckoutOutput($result['url'], $result['id']);
    }
}
