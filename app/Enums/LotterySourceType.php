<?php

namespace App\Enums;

use App\Util\EnumBase;

class LotterySourceType extends EnumBase
{
    public const SMAREGI_TRANSACTION = 'smaregi_transaction';

    public const ORDER = 'order';

    protected static array $values = [
        self::SMAREGI_TRANSACTION => 'スマレジ取引',
        self::ORDER => 'アプリ注文',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
