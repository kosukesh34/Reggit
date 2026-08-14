<?php

namespace App\Enums;

use App\Util\EnumBase;

class ScheduledDeliveryCategory extends EnumBase
{
    public const CONDITION = 'condition';

    public const RESERVATION = 'reservation';

    /** @var array<string, string> */
    protected static array $values = [
        self::CONDITION => '条件達成配信',
        self::RESERVATION => '予約配信',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
