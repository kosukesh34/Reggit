<?php

namespace App\Enums;

use App\Util\EnumBase;

class ScheduledDeliveryContentType extends EnumBase
{
    public const ANNOUNCEMENT = 'announcement';

    public const COUPON_ONLY = 'coupon_only';

    /** @var array<string, string> */
    protected static array $values = [
        self::ANNOUNCEMENT => 'お知らせ（クーポン同時配布可）',
        self::COUPON_ONLY => 'クーポンのみ',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
