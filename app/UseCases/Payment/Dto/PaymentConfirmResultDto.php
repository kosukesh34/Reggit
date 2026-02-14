<?php

namespace App\UseCases\Payment\Dto;

use App\Models\Order;

class PaymentConfirmResultDto
{
    public function __construct(
        public bool $isSuccess,
        public string $message,
        public ?string $status,
        public ?Order $order
    ) {
    }
}
