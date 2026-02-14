<?php

namespace App\Enums;

use App\Util\EnumBase;

class QueueStatus extends EnumBase
{
    protected static array $values = [
        'waiting' => '待ち',
        'called' => '呼び出し',
        'entered' => '入店済み',
        'cancelled' => 'キャンセル',
    ];

    public const WAITING = 'waiting';
    public const CALLED = 'called';
    public const ENTERED = 'entered';
    public const CANCELLED = 'cancelled';
}
