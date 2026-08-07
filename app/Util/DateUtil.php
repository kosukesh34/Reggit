<?php

namespace App\Util;

use Carbon\Carbon;

final class DateUtil
{
    public static function nullableCarbon(Carbon|\DateTimeInterface|string|int|float|null $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
