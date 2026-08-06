<?php

namespace App\Enums;

use App\Util\EnumBase;

class SmaregiEvent extends EnumBase
{
    protected static array $values = [
        'AppSubscription' => '利用者契約通知',
        'pos:customers' => '会員',
        'pos:customer-points' => '会員ポイント',
        'pos:transactions' => '取引',
    ];

    public const APP_SUBSCRIPTION = 'AppSubscription';

    public const POS_CUSTOMERS = 'pos:customers';

    public const POS_CUSTOMER_POINTS = 'pos:customer-points';

    public const POS_TRANSACTIONS = 'pos:transactions';
}
