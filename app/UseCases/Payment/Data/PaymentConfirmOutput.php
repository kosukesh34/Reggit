<?php

namespace App\UseCases\Payment\Data;

use App\Models\Order;

/** 決済確認 UseCase の出力。不変。 */
class PaymentConfirmOutput
{
    public function __construct(
        public bool $isSuccess,
        public string $message,
        public ?string $status,
        public ?Order $order
    ) {}
}
