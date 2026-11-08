<?php

namespace App\Enums;

use App\Util\EnumBase;

class OrderStatus extends EnumBase
{
    protected static array $values = [
        'pending' => '未処理',
        'confirmed' => '確認済み',
        'preparing' => '準備中',
        'ready' => '準備完了',
        'shipped' => '発送済み',
        'completed' => '完了',
        'cancelled' => 'キャンセル',
    ];

    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const PREPARING = 'preparing';

    public const READY = 'ready';

    public const SHIPPED = 'shipped';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    /** 管理画面のバッジ色（badge-*） */
    public static function badgeColor(string $value): string
    {
        return match ($value) {
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            default => 'info',
        };
    }
}
