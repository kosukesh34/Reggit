<?php

namespace App\Enums;

use App\Util\EnumBase;

class SmaregiEvent extends EnumBase
{
    protected static array $values = [
        'AppSubscription' => '利用者契約通知',
        'pos:customer-points' => '会員ポイント',
    ];

    public const APP_SUBSCRIPTION = 'AppSubscription';

    public const CUSTOMER_POINTS = 'pos:customer-points';
}
