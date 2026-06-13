<?php

namespace App\Enums;

use App\Util\EnumBase;

class CouponDeliveryType extends EnumBase
{
    protected static array $values = [
        self::IMMEDIATE_ALL => '全員に即時配布',
        self::IMMEDIATE_PERSONAL => '個人指定（即時）',
        self::IMMEDIATE_SEGMENT_SALES => '売上条件で絞り込み（即時）',
        self::IMMEDIATE_BIRTHDAY => '今月が誕生月（即時）',
        self::IMMEDIATE_NEW_MEMBERS => '新規会員（即時）',
        self::IMMEDIATE_INACTIVE => '来店・購入なし（即時）',
        self::SCHEDULED_ALL => '全員に予約配布',
        self::SCHEDULED_PERSONAL => '個人指定（予約）',
        self::SCHEDULED_SEGMENT_SALES => '売上条件で絞り込み（予約）',
        self::SCHEDULED_BIRTHDAY => '今月が誕生月（予約）',
        self::SCHEDULED_NEW_MEMBERS => '新規会員（予約）',
        self::SCHEDULED_INACTIVE => '来店・購入なし（予約）',
    ];

    public const IMMEDIATE_ALL = 'immediate_all';

    public const IMMEDIATE_PERSONAL = 'immediate_personal';

    public const IMMEDIATE_SEGMENT_SALES = 'immediate_segment_sales';

    public const IMMEDIATE_BIRTHDAY = 'immediate_birthday';

    public const IMMEDIATE_NEW_MEMBERS = 'immediate_new_members';

    public const IMMEDIATE_INACTIVE = 'immediate_inactive';

    public const SCHEDULED_ALL = 'scheduled_all';

    public const SCHEDULED_PERSONAL = 'scheduled_personal';

    public const SCHEDULED_SEGMENT_SALES = 'scheduled_segment_sales';

    public const SCHEDULED_BIRTHDAY = 'scheduled_birthday';

    public const SCHEDULED_NEW_MEMBERS = 'scheduled_new_members';

    public const SCHEDULED_INACTIVE = 'scheduled_inactive';

    /** @return list<string> */
    public static function scheduledValues(): array
    {
        return [
            self::SCHEDULED_ALL,
            self::SCHEDULED_PERSONAL,
            self::SCHEDULED_SEGMENT_SALES,
            self::SCHEDULED_BIRTHDAY,
            self::SCHEDULED_NEW_MEMBERS,
            self::SCHEDULED_INACTIVE,
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

    public static function isScheduled(string $value): bool
    {
        return in_array($value, self::scheduledValues(), true);
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
                    self::IMMEDIATE_BIRTHDAY,
                    self::IMMEDIATE_NEW_MEMBERS,
                    self::IMMEDIATE_INACTIVE,
                ],
            ],
            'scheduled' => [
                'label' => '予約配布',
                'icon' => 'fa-clock',
                'types' => [
                    self::SCHEDULED_ALL,
                    self::SCHEDULED_PERSONAL,
                    self::SCHEDULED_SEGMENT_SALES,
                    self::SCHEDULED_BIRTHDAY,
                    self::SCHEDULED_NEW_MEMBERS,
                    self::SCHEDULED_INACTIVE,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     scheduledTypes: list<string>,
     *     personalTypes: list<string>,
     *     segmentTypes: list<string>,
     *     birthdayTypes: list<string>,
     *     newMemberTypes: list<string>,
     *     inactiveTypes: list<string>
     * }
     */
    public static function jsConfig(): array
    {
        return [
            'scheduledTypes' => self::scheduledValues(),
            'personalTypes' => self::personalValues(),
            'segmentTypes' => self::segmentValues(),
            'birthdayTypes' => self::birthdayValues(),
            'newMemberTypes' => self::newMemberValues(),
            'inactiveTypes' => self::inactiveValues(),
        ];
    }
}
