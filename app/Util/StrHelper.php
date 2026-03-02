<?php

namespace App\Util;

final class StrHelper
{
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
