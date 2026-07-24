<?php

namespace App\Enums;

use App\Util\EnumBase;

class LotteryRewardType extends EnumBase
{
    public const GACHA = 'gacha';

    public const SCRATCH = 'scratch';

    protected static array $values = [
        self::GACHA => 'ガチャ',
        self::SCRATCH => 'スクラッチ',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
