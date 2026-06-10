<?php

namespace App\Util;

use Carbon\Carbon;
use DateTimeInterface;

final class DateUtil
{
    /**
     * @param  string|DateTimeInterface|null  $value
     */
    public static function nullableCarbon($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse($value);
    }
}
