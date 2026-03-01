<?php

namespace App\Enums;

use App\Util\EnumBase;

class OrderType extends EnumBase
{
    protected static array $values = [
        'mobile' => 'モバイル',
        'in_store' => '店内',
    ];

    public const MOBILE = 'mobile';

    public const IN_STORE = 'in_store';
}
