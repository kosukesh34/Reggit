<?php

namespace App\Enums;

use App\Util\EnumBase;

class RfmSegment extends EnumBase
{
    public const CHAMPION = 'champion';

    public const LOYAL = 'loyal';

    public const ACTIVE = 'active';

    public const AT_RISK = 'at_risk';

    public const DORMANT = 'dormant';

    public const NEVER = 'never';

    /** @var array<string, string> */
    protected static array $values = [
        self::CHAMPION => '優良顧客',
        self::LOYAL => 'ロイヤル顧客',
        self::ACTIVE => 'アクティブ',
        self::AT_RISK => '離反リスク',
        self::DORMANT => '休眠顧客',
        self::NEVER => '未購買',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }
}
