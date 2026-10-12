<?php

namespace App\Enums;

use App\Util\EnumBase;

class LandingInquiryPlan extends EnumBase
{
    protected static array $values = [
        self::LINE => 'LINEプラン',
        self::NATIVE => 'ネイティブアプリプラン',
        self::UNDECIDED => 'まだ決めていない',
    ];

    public const LINE = 'line';

    public const NATIVE = 'native';

    public const UNDECIDED = 'undecided';
}
