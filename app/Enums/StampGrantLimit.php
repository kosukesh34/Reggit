<?php

namespace App\Enums;

use App\Util\EnumBase;

class StampGrantLimit extends EnumBase
{
    public const PER_MEMBER = 'member';

    public const ONCE = 'once';

    public const UNLIMITED = 'unlimited';

    public const DEFAULT_EXPIRES_MINUTES = 15;

    public const MAX_AMOUNT = 20;

    /** @var array<string, string> */
    protected static array $values = [
        self::PER_MEMBER => 'お客様1人につき1回',
        self::ONCE => '全体で1回だけ（1度きり）',
        self::UNLIMITED => '期限内は何度でも',
    ];

    /** @var array<int, string> */
    public const EXPIRES_OPTIONS = [
        15 => '15分',
        30 => '30分',
        60 => '1時間',
        180 => '3時間',
        1440 => '1日',
        10080 => '7日',
    ];

    public static function normalize(string $value): string
    {
        return array_key_exists($value, static::$values) ? $value : self::PER_MEMBER;
    }

    public static function normalizeAmount(int $amount): int
    {
        return max(1, min(self::MAX_AMOUNT, $amount));
    }

    public static function normalizeExpiresMinutes(int $minutes): int
    {
        return isset(self::EXPIRES_OPTIONS[$minutes]) ? $minutes : self::DEFAULT_EXPIRES_MINUTES;
    }
}
