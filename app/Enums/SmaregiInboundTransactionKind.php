<?php

namespace App\Enums;

use App\Util\EnumBase;

class SmaregiInboundTransactionKind extends EnumBase
{
    protected static array $values = [
        'sale' => '通常販売',
        'return' => '返品',
        'cancelled' => '取消',
        'skip' => 'スキップ',
    ];

    public const SALE = 'sale';

    public const RETURN = 'return';

    public const CANCELLED = 'cancelled';

    public const SKIP = 'skip';
}
