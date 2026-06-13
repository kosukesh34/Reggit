<?php

namespace App\Enums;

use App\Util\EnumBase;

class CouponDeliveryStatus extends EnumBase
{
    protected static array $values = [
        self::SCHEDULED => '予約中',
        self::SENT => '配布済み',
        self::FAILED => '失敗',
        self::CANCELLED => 'キャンセル',
    ];

    public const SCHEDULED = 'scheduled';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';
}
