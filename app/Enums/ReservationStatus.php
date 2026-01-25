<?php

namespace App\Enums;

use App\Util\EnumBase;

class ReservationStatus extends EnumBase
{
    protected static array $values = [
        'pending' => '予約待ち',
        'confirmed' => '予約確定',
        'cancelled' => 'キャンセル',
        'completed' => '完了',
    ];

    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const CANCELLED = 'cancelled';
    public const COMPLETED = 'completed';
}

