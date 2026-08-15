<?php

namespace App\Enums;

use App\Util\EnumBase;

class RankAggregationType extends EnumBase
{
    public const TOTAL = 'total';

    public const PERIOD = 'period';

    /** @var array<string, string> */
    protected static array $values = [
        self::TOTAL => '累計',
        self::PERIOD => '期間',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }

    public static function normalize(?string $value): string
    {
        $value = (string) $value;

        return array_key_exists($value, static::$values) ? $value : self::TOTAL;
    }

    public static function isPeriod(?string $value): bool
    {
        return self::normalize($value) === self::PERIOD;
    }
}
