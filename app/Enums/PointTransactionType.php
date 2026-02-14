<?php

namespace App\Enums;

use App\Util\EnumBase;

class PointTransactionType extends EnumBase
{
    protected static array $values = [
        'earned' => '獲得',
        'used' => '使用',
        'refunded' => '返却',
        'reversed' => '取り消し',
        'adjusted' => '調整',
        'expired' => '期限切れ',
    ];

    public const EARNED = 'earned';
    public const USED = 'used';
    public const REFUNDED = 'refunded';
    public const REVERSED = 'reversed';
    public const ADJUSTED = 'adjusted';
    public const EXPIRED = 'expired';
}
