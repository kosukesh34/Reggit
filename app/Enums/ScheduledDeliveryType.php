<?php

namespace App\Enums;

use App\Util\EnumBase;

class ScheduledDeliveryType extends EnumBase
{
    public const NEW_USER_MINUTES = 'new_user_push_1';

    public const NEW_USER_DAYS = 'new_user_push_2';

    public const LAST_VISIT = 'last_shop_push';

    public const VISIT_MILESTONE = 'milestone_shop_push';

    public const RANK_UP_ARRIVAL = 'rank_up_arrival';

    public const TOTAL_POINTS = 'total_point_push';

    public const TOTAL_STAMPS = 'total_stamp_push';

    public const BIRTHDAY_DAYS_BEFORE = 'birthday_push_1';

    public const BIRTHDAY_MONTH_FIRST = 'birthday_push_2';

    public const BIRTHDAY_NEXT_MONTH = 'birthday_next_month';

    public const WEEKLY = 'weekly_push';

    public const MONTHLY = 'monthly_push';

    public const LAST_MONTH_VISITS = 'last_month_stamp_push';

    /** @var array<string, string> */
    protected static array $values = [
        self::NEW_USER_MINUTES => '新規入会後（分）',
        self::NEW_USER_DAYS => '新規入会後（日）',
        self::LAST_VISIT => '最終来店後',
        self::VISIT_MILESTONE => '節目来店回数',
        self::RANK_UP_ARRIVAL => 'ランクアップ達成',
        self::TOTAL_POINTS => 'ポイント累計数',
        self::TOTAL_STAMPS => 'スタンプ累計数',
        self::BIRTHDAY_DAYS_BEFORE => '誕生日のN日前',
        self::BIRTHDAY_MONTH_FIRST => '誕生月の1日',
        self::BIRTHDAY_NEXT_MONTH => '来月誕生日の人',
        self::WEEKLY => '週刊配信',
        self::MONTHLY => '月刊配信',
        self::LAST_MONTH_VISITS => '前月来店回数',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }

    /** @return list<string> */
    public static function conditionValues(): array
    {
        return [
            self::NEW_USER_MINUTES,
            self::NEW_USER_DAYS,
            self::LAST_VISIT,
            self::VISIT_MILESTONE,
            self::RANK_UP_ARRIVAL,
            self::TOTAL_POINTS,
            self::TOTAL_STAMPS,
        ];
    }

    /** @return list<string> */
    public static function reservationValues(): array
    {
        return [
            self::BIRTHDAY_DAYS_BEFORE,
            self::BIRTHDAY_MONTH_FIRST,
            self::BIRTHDAY_NEXT_MONTH,
            self::WEEKLY,
            self::MONTHLY,
            self::LAST_MONTH_VISITS,
        ];
    }

    public static function categoryOf(string $type): string
    {
        return in_array($type, self::reservationValues(), true)
            ? ScheduledDeliveryCategory::RESERVATION
            : ScheduledDeliveryCategory::CONDITION;
    }

    public static function runsOnCron(string $type): bool
    {
        return ! in_array($type, [self::NEW_USER_MINUTES, self::RANK_UP_ARRIVAL], true);
    }

    public static function needsClock(string $type): bool
    {
        return self::runsOnCron($type);
    }

    public static function isOneShot(string $type): bool
    {
        return in_array($type, [
            self::NEW_USER_MINUTES,
            self::NEW_USER_DAYS,
            self::LAST_VISIT,
            self::VISIT_MILESTONE,
            self::RANK_UP_ARRIVAL,
            self::TOTAL_POINTS,
            self::TOTAL_STAMPS,
        ], true);
    }
}
