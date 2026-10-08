<?php

namespace App\Services\Payment;

use App\Models\Order;

interface UnpaidPaymentSessionCanceller
{
    public function cancel(Order $order, string $channel, string $sessionId): void;
}
