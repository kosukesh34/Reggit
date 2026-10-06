<?php

namespace App\Enums;

use App\Util\EnumBase;

class SegmentCombineMode extends EnumBase
{
    public const ALL = 'all';

    public const ANY = 'any';

    /** @var array<string, string> */
    protected static array $values = [
        self::ANY => 'いずれか',
        self::ALL => 'すべて',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }

    public static function fromInput(mixed $value): string
    {
        return $value === self::ALL ? self::ALL : self::ANY;
    }
}
