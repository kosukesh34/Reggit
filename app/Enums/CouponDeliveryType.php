<?php

namespace App\Enums;

use App\Util\EnumBase;

class CouponDeliveryType extends EnumBase
{
    protected static array $values = [
        self::IMMEDIATE_ALL => '全員に即時配布',
        self::IMMEDIATE_PERSONAL => '個人指定（即時）',
        self::IMMEDIATE_SEGMENT_SALES => '売上条件で絞り込み（即時）',
        self::IMMEDIATE_SEGMENT_ATTRIBUTES => '属性で絞り込み（即時）',
        self::IMMEDIATE_BIRTHDAY => '今月が誕生月（即時）',
        self::IMMEDIATE_NEW_MEMBERS => '新規会員（即時）',
        self::IMMEDIATE_INACTIVE => '来店・購入なし（即時）',
        self::IMMEDIATE_RANK => '指定ランク（即時）',
        self::IMMEDIATE_STAMP => 'スタンプ累計（即時）',
        self::IMMEDIATE_DAYS_AFTER_VISIT => '最終来店からN日後（即時）',
        self::SCHEDULED_ALL => '全員に予約配布',
        self::SCHEDULED_PERSONAL => '個人指定（予約）',
        self::SCHEDULED_SEGMENT_SALES => '売上条件で絞り込み（予約）',
        self::SCHEDULED_SEGMENT_ATTRIBUTES => '属性で絞り込み（予約）',
        self::SCHEDULED_BIRTHDAY => '今月が誕生月（予約）',
        self::SCHEDULED_NEW_MEMBERS => '新規会員（予約）',
        self::SCHEDULED_INACTIVE => '来店・購入なし（予約）',
        self::SCHEDULED_RANK => '指定ランク（予約）',
        self::SCHEDULED_STAMP => 'スタンプ累計（予約）',
        self::SCHEDULED_DAYS_AFTER_VISIT => '最終来店からN日後（予約）',
        self::EVENT_RANK_UP_ARRIVAL => 'ランクアップ達成配信',
        self::EVENT_WEATHER_RAIN => '雨の日自動配布',
    ];

    public const IMMEDIATE_ALL = 'immediate_all';

    public const IMMEDIATE_PERSONAL = 'immediate_personal';

    public const IMMEDIATE_SEGMENT_SALES = 'immediate_segment_sales';

    public const IMMEDIATE_SEGMENT_ATTRIBUTES = 'immediate_segment_attributes';

    public const IMMEDIATE_BIRTHDAY = 'immediate_birthday';

    public const IMMEDIATE_NEW_MEMBERS = 'immediate_new_members';

    public const IMMEDIATE_INACTIVE = 'immediate_inactive';

    public const IMMEDIATE_RANK = 'immediate_rank';

    public const IMMEDIATE_STAMP = 'immediate_stamp';

    public const IMMEDIATE_DAYS_AFTER_VISIT = 'immediate_days_after_visit';

    public const SCHEDULED_ALL = 'scheduled_all';

    public const SCHEDULED_PERSONAL = 'scheduled_personal';

    public const SCHEDULED_SEGMENT_SALES = 'scheduled_segment_sales';

    public const SCHEDULED_SEGMENT_ATTRIBUTES = 'scheduled_segment_attributes';

    public const SCHEDULED_BIRTHDAY = 'scheduled_birthday';

    public const SCHEDULED_NEW_MEMBERS = 'scheduled_new_members';

    public const SCHEDULED_INACTIVE = 'scheduled_inactive';

    public const SCHEDULED_RANK = 'scheduled_rank';

    public const SCHEDULED_STAMP = 'scheduled_stamp';

    public const SCHEDULED_DAYS_AFTER_VISIT = 'scheduled_days_after_visit';

    public const EVENT_RANK_UP_ARRIVAL = 'event_rank_up_arrival';

    /** 雨の日クーポンの自動配布。履歴の表示用で、手動の配布では選べない */
    public const EVENT_WEATHER_RAIN = 'event_weather_rain';

    /** @return list<string> */
    public static function scheduledValues(): array
    {
        return [
            self::SCHEDULED_ALL,
            self::SCHEDULED_PERSONAL,
            self::SCHEDULED_SEGMENT_SALES,
            self::SCHEDULED_SEGMENT_ATTRIBUTES,
            self::SCHEDULED_BIRTHDAY,
            self::SCHEDULED_NEW_MEMBERS,
            self::SCHEDULED_INACTIVE,
            self::SCHEDULED_RANK,
            self::SCHEDULED_STAMP,
            self::SCHEDULED_DAYS_AFTER_VISIT,
        ];
    }

    /** @return list<string> */
    public static function personalValues(): array
    {
        return [self::IMMEDIATE_PERSONAL, self::SCHEDULED_PERSONAL];
    }

    /** @return list<string> */
    public static function segmentValues(): array
    {
        return [self::IMMEDIATE_SEGMENT_SALES, self::SCHEDULED_SEGMENT_SALES];
    }

    /** @return list<string> */
    public static function attributeSegmentValues(): array
    {
        return [self::IMMEDIATE_SEGMENT_ATTRIBUTES, self::SCHEDULED_SEGMENT_ATTRIBUTES];
    }

    /** @return list<string> */
    public static function birthdayValues(): array
    {
        return [self::IMMEDIATE_BIRTHDAY, self::SCHEDULED_BIRTHDAY];
    }

    /** @return list<string> */
    public static function newMemberValues(): array
    {
        return [self::IMMEDIATE_NEW_MEMBERS, self::SCHEDULED_NEW_MEMBERS];
    }

    /** @return list<string> */
    public static function inactiveValues(): array
    {
        return [self::IMMEDIATE_INACTIVE, self::SCHEDULED_INACTIVE];
    }

    /** @return list<string> */
    public static function rankValues(): array
    {
        return [self::IMMEDIATE_RANK, self::SCHEDULED_RANK];
    }

    /** @return list<string> */
    public static function stampValues(): array
    {
        return [self::IMMEDIATE_STAMP, self::SCHEDULED_STAMP];
    }

    /** @return list<string> */
    public static function daysAfterVisitValues(): array
    {
        return [self::IMMEDIATE_DAYS_AFTER_VISIT, self::SCHEDULED_DAYS_AFTER_VISIT];
    }

    /** @return list<string> */
    public static function eventRankUpValues(): array
    {
        return [self::EVENT_RANK_UP_ARRIVAL];
    }

    public static function isScheduled(string $value): bool
    {
        return in_array($value, self::scheduledValues(), true);
    }

    public static function isEventRankUpArrival(string $value): bool
    {
        return $value === self::EVENT_RANK_UP_ARRIVAL;
    }

    /** 空の member_ids が「全員配布」を意味する配信種別 */
    public static function isAllMembersDelivery(string $value): bool
    {
        return in_array($value, [self::IMMEDIATE_ALL, self::SCHEDULED_ALL], true);
    }

    /**
     * @return array<string, array{label: string, icon: string, types: list<string>}>
     */
    public static function formGroups(): array
    {
        return [
            'immediate' => [
                'label' => '即時配布',
                'icon' => 'fa-bolt',
                'types' => [self::IMMEDIATE_ALL, self::IMMEDIATE_PERSONAL],
            ],
            'segment' => [
                'label' => '絞り込み配布（即時）',
                'icon' => 'fa-filter',
                'types' => [
                    self::IMMEDIATE_SEGMENT_SALES,
                    self::IMMEDIATE_SEGMENT_ATTRIBUTES,
                    self::IMMEDIATE_BIRTHDAY,
                    self::IMMEDIATE_NEW_MEMBERS,
                    self::IMMEDIATE_INACTIVE,
                    self::IMMEDIATE_RANK,
                    self::IMMEDIATE_STAMP,
                    self::IMMEDIATE_DAYS_AFTER_VISIT,
                    self::EVENT_RANK_UP_ARRIVAL,
                ],
            ],
            'scheduled' => [
                'label' => '予約配布',
                'icon' => 'fa-clock',
                'types' => [
                    self::SCHEDULED_ALL,
                    self::SCHEDULED_PERSONAL,
                    self::SCHEDULED_SEGMENT_SALES,
                    self::SCHEDULED_SEGMENT_ATTRIBUTES,
                    self::SCHEDULED_BIRTHDAY,
                    self::SCHEDULED_NEW_MEMBERS,
                    self::SCHEDULED_INACTIVE,
                    self::SCHEDULED_RANK,
                    self::SCHEDULED_STAMP,
                    self::SCHEDULED_DAYS_AFTER_VISIT,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     scheduledTypes: list<string>,
     *     personalTypes: list<string>,
     *     segmentTypes: list<string>,
     *     attributeSegmentTypes: list<string>,
     *     birthdayTypes: list<string>,
     *     newMemberTypes: list<string>,
     *     inactiveTypes: list<string>,
     *     rankTypes: list<string>,
     *     stampTypes: list<string>,
     *     daysAfterVisitTypes: list<string>,
     *     eventRankUpTypes: list<string>
     * }
     */
    public static function jsConfig(): array
    {
        return [
            'scheduledTypes' => self::scheduledValues(),
            'personalTypes' => self::personalValues(),
            'segmentTypes' => self::segmentValues(),
            'attributeSegmentTypes' => self::attributeSegmentValues(),
            'birthdayTypes' => self::birthdayValues(),
            'newMemberTypes' => self::newMemberValues(),
            'inactiveTypes' => self::inactiveValues(),
            'rankTypes' => self::rankValues(),
            'stampTypes' => self::stampValues(),
            'daysAfterVisitTypes' => self::daysAfterVisitValues(),
            'eventRankUpTypes' => self::eventRankUpValues(),
        ];
    }
}
