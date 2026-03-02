<?php

namespace App\Enums;

use App\Util\EnumBase;

class PaymentMethod extends EnumBase
{
    protected static array $values = [
        'cash' => '現金',
        'points' => 'ポイント',
        'stripe' => 'Stripe',
    ];

    public const CASH = 'cash';

    public const POINTS = 'points';

    public const STRIPE = 'stripe';
}
