<?php

namespace App\Enums;

use App\Util\EnumBase;

class LotteryGrantTrigger extends EnumBase
{
    public const EVERY_PURCHASE = 'every_purchase';

    public const ONCE_PER_DAY = 'once_per_day';

    public const MIN_AMOUNT = 'min_amount';

    protected static array $values = [
        self::EVERY_PURCHASE => '会計・注文のたびに1回',
        self::ONCE_PER_DAY => '1日1回まで',
        self::MIN_AMOUNT => '最低金額以上の取引のみ',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
