<?php

namespace App\Enums;

use App\Util\EnumBase;

class CouponApplyConditionDivision extends EnumBase
{
    public const ALL = '0';

    public const INCLUDE_TARGET_PRODUCT = '1';

    /** @var array<string, string> */
    protected static array $values = [
        self::ALL => '全適用',
        self::INCLUDE_TARGET_PRODUCT => '対象商品が含まれる場合のみ適用',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_map('strval', array_keys(static::$values));
    }
}
