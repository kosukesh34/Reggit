<?php

namespace App\Enums;

use App\Models\MemberStamp;
use App\Util\EnumBase;

class StampCycleMode extends EnumBase
{
    public const REPEAT = 'repeat';

    public const ONCE = 'once';

    public const NEXT_CARD = 'next_card';

    /** @var array<string, string> */
    protected static array $values = [
        self::REPEAT => '同じカードを繰り返す',
        self::ONCE => '1周で終了する',
        self::NEXT_CARD => '達成したら別のカードに切り替える',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(static::$values);
    }

    public static function normalize(?string $value): string
    {
        $value = (string) $value;

        return array_key_exists($value, static::$values) ? $value : self::REPEAT;
    }

    public static function isGrantClosed(string $cycleMode, ?MemberStamp $memberStamp): bool
    {
        if ($memberStamp === null || $memberStamp->completed_at === null) {
            return false;
        }

        return self::normalize($cycleMode) !== self::REPEAT;
    }

    public static function grantSuccessMessage(
        string $cycleMode,
        int $amount,
        bool $completedNow,
        int $cycleCount,
        ?string $nextCardName,
    ): string {
        $base = 'スタンプを'.$amount.'個追加しました';
        if ($completedNow !== true) {
            return $base;
        }
        $mode = self::normalize($cycleMode);
        if ($mode === self::NEXT_CARD && $nextCardName !== null && $nextCardName !== '') {
            return $base.'。次は「'.$nextCardName.'」です';
        }
        if ($mode === self::REPEAT && $cycleCount > 0) {
            return $base.'。'.$cycleCount.'周達成しました';
        }

        return $base.'。カードを達成しました';
    }
}
