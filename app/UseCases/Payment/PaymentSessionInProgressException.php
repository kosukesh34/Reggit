<?php

namespace App\UseCases\Payment;

use RuntimeException;

class PaymentSessionInProgressException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('決済画面を作成中です。少し待ってから再度お試しください');
    }
}
