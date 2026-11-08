<?php

namespace App\Enums;

use App\Util\EnumBase;

class ReservationStatus extends EnumBase
{
    protected static array $values = [
        'pending' => '予約待ち',
        'confirmed' => '確認済み',
        'cancelled' => 'キャンセル',
        'completed' => '完了',
    ];

    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const CANCELLED = 'cancelled';

    public const COMPLETED = 'completed';

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
