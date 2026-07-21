<?php

namespace App\Util;

final class StrHelper
{
    public static function trimmedOrEmpty(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    public static function isEmptyStringOrNull($value): bool
    {
        if ($value === null) {
            return true;
        }
        if (! is_string($value)) {
            return false;
        }

        return $value === '';
    }
}
