<?php

namespace App\Enums;

use App\Util\EnumBase;

class CouponCombineDivision extends EnumBase
{
    public const FORBIDDEN = '1';

    public const PARTIAL = '2';

    public const ALLOWED = '3';

    /** @var array<string, string> */
    protected static array $values = [
        self::FORBIDDEN => '併用不可',
        self::PARTIAL => '一部のクーポンと併用可能',
        self::ALLOWED => '併用可能',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_map('strval', array_keys(static::$values));
    }
}
