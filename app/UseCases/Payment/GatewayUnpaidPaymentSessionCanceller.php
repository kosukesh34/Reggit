<?php

namespace App\UseCases\Payment;

use App\Models\Order;
use App\Services\Payment\UnpaidPaymentSessionCanceller;

class GatewayUnpaidPaymentSessionCanceller implements UnpaidPaymentSessionCanceller
{
    public function __construct(
        private ProjectPaymentGatewayResolver $resolver,
        private PaymentGateway $gateway,
    ) {}

    public function cancel(Order $order, string $channel, string $sessionId): void
    {
        $this->resolver->forOrder($order, $this->gateway)->cancelUnpaidSession($channel, $sessionId);
    }
}
