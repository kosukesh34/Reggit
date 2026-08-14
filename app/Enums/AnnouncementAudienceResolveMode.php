<?php

namespace App\Enums;

use App\Util\EnumBase;

class AnnouncementAudienceResolveMode extends EnumBase
{
    public const SNAPSHOT = 'snapshot';

    public const AT_SEND = 'at_send';

    /** @var array<string, string> */
    protected static array $values = [
        self::SNAPSHOT => '今の対象者で固定する',
        self::AT_SEND => '配信時に条件を再評価する',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
