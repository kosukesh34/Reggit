<?php

namespace App\Enums;

use App\Util\EnumBase;

class OrderStatus extends EnumBase
{
    protected static array $values = [
        'pending' => '未対応',
        'confirmed' => '確定',
        'preparing' => '準備中',
        'ready' => '受け取り可能',
        'completed' => '完了',
        'cancelled' => 'キャンセル',
    ];

    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const PREPARING = 'preparing';

    public const READY = 'ready';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';
}
