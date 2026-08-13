<?php

namespace App\Enums;

use App\Util\EnumBase;

class StampRewardType extends EnumBase
{
    public const TEXT = 'text';

    public const COUPON = 'coupon';

    public const GACHA = 'gacha';

    public const GACHA_DESCRIPTION = 'ガチャ1回';

    protected static array $values = [
        self::TEXT => 'テキスト',
        self::COUPON => 'クーポン',
        self::GACHA => 'ガチャ',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }

    public static function normalize(string $value): string
    {
        return array_key_exists($value, static::$values) ? $value : self::TEXT;
    }
}
