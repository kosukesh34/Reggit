<?php

namespace App\Enums;

use App\Util\EnumBase;

class SmaregiEvent extends EnumBase
{
    protected static array $values = [
        'AppSubscription' => '利用者契約通知',
    ];

    public const APP_SUBSCRIPTION = 'AppSubscription';
}
