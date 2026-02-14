<?php

namespace App\Enums;

use App\Util\EnumBase;

class PaymentIntentStatus extends EnumBase
{
    protected static array $values = [
        'succeeded' => '成功',
        'requires_payment_method' => '支払い方法が必要',
        'requires_action' => 'アクション必要',
        'processing' => '処理中',
        'canceled' => 'キャンセル',
    ];

    public const SUCCEEDED = 'succeeded';
    public const REQUIRES_PAYMENT_METHOD = 'requires_payment_method';
    public const REQUIRES_ACTION = 'requires_action';
    public const PROCESSING = 'processing';
    public const CANCELED = 'canceled';
}
