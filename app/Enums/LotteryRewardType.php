<?php

namespace App\Enums;

use App\Util\EnumBase;

class LotteryRewardType extends EnumBase
{
    public const GACHA = 'gacha';

    public const SCRATCH = 'scratch';

    public const ROULETTE = 'roulette';

    protected static array $values = [
        self::GACHA => 'ガチャ',
        self::SCRATCH => 'スクラッチ',
        self::ROULETTE => 'ルーレット',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
