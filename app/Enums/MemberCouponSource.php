<?php

namespace App\Enums;

use App\Util\EnumBase;

class MemberCouponSource extends EnumBase
{
    public const SMAREGI_TRANSACTION = 'smaregi_transaction';

    public const ORDER = 'order';

    public const ANNOUNCEMENT = 'announcement';

    public const SCHEDULED_DELIVERY = 'scheduled_delivery';

    public const RANK_UP = 'rank_up';

    public const WEATHER_RAIN = 'weather_rain';

    /** @var array<string, string> */
    protected static array $values = [
        self::SMAREGI_TRANSACTION => 'スマレジ取引',
        self::ORDER => 'アプリ注文',
        self::ANNOUNCEMENT => 'お知らせ配信',
        self::SCHEDULED_DELIVERY => 'スケジュール配信',
        self::RANK_UP => 'ランクアップ',
        self::WEATHER_RAIN => '雨の日クーポン',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
