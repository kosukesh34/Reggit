<?php

namespace App\UseCases\Payment\Data;

use App\Models\Order;

/** 決済確認 UseCase の実行結果。不変。 */
class PaymentConfirmResult
{
    public function __construct(
        public bool $isSuccess,
        public string $message,
        public ?string $status,
        public ?Order $order
    ) {
    }
}
