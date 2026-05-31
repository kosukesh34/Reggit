<?php

namespace App\Enums;

use App\Util\EnumBase;

class SmaregiTransactionHeadDivision extends EnumBase
{
    protected static array $values = [
        '1' => '通常',
        '6' => 'ポイント加算',
        '7' => 'ポイント減算',
        '8' => 'ポイント失効',
    ];

    public const PURCHASE = '1';

    public const POINT_ADD = '6';

    public const POINT_SUBTRACT = '7';

    public const POINT_EXPIRE = '8';
}
