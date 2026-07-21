<?php

namespace App\Enums;

use App\Util\EnumBase;

class OrderType extends EnumBase
{
    protected static array $values = [
        'mobile' => 'モバイル注文',
        'in_store' => '店内注文',
        'pos' => 'POS会計',
    ];

    public const MOBILE = 'mobile';

    public const IN_STORE = 'in_store';

    public const POS = 'pos';
}
