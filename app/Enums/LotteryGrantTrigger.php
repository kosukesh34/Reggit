<?php

namespace App\Enums;

use App\Util\EnumBase;

class LotteryGrantTrigger extends EnumBase
{
    public const EVERY_PURCHASE = 'every_purchase';

    public const ONCE_PER_DAY = 'once_per_day';

    public const MIN_AMOUNT = 'min_amount';

    protected static array $values = [
        self::EVERY_PURCHASE => '購入金額に関係なく、購入ごとに付与',
        self::ONCE_PER_DAY => '購入したお客様へ1日1回まで付与',
        self::MIN_AMOUNT => '設定金額以上を購入した場合に付与',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
