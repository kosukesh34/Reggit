<?php

namespace App\Util;

abstract class EnumBase
{
    protected static array $values = [];

    public static function getValues(): array
    {
        return static::$values;
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, static::$values, true);
    }

    public static function getLabel(string $value): string
    {
        return static::$values[$value] ?? $value;
    }
}

