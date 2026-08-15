<?php

namespace App\Enums;

use App\Util\EnumBase;

class MemberNotificationPreference extends EnumBase
{
    public const ALL = 'all';

    public const COUPON_ONLY = 'coupon_only';

    public const NONE = 'none';

    /** @var array<string, string> */
    protected static array $values = [
        self::ALL => 'すべてのお知らせを受け取る',
        self::COUPON_ONLY => 'クーポン情報のみ受け取る',
        self::NONE => '通知をすべて受け取らない',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }

    public static function normalize(?string $value): string
    {
        return array_key_exists((string) $value, static::$values) ? (string) $value : self::ALL;
    }

    public static function allowsAnnouncement(?string $value, bool $hasCoupon): bool
    {
        return match (self::normalize($value)) {
            self::ALL => true,
            self::COUPON_ONLY => $hasCoupon,
            self::NONE => false,
        };
    }

    public static function allowsCoupon(?string $value): bool
    {
        return self::normalize($value) !== self::NONE;
    }

    /** @return list<string> */
    public static function eligibleValues(bool $hasCoupon): array
    {
        return $hasCoupon
            ? [self::ALL, self::COUPON_ONLY]
            : [self::ALL];
    }
}
