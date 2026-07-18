<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * 日次の受付・注文番号。
 *
 * 表示（ユーザー向け）: 日 + 通番（例: 4/20 の 1番目 → 201、20番目 → 2020、2022番目 → 202022）
 * DB 保存: YYYYMMDD + 通番（例: 202604201, 2026042020, 202604202022）
 */
final class DailySequenceNumber
{
    public static function nextSeq(int $maxSeq): int
    {
        return max(0, $maxSeq) + 1;
    }

    public static function datePrefix(?CarbonInterface $date = null): string
    {
        return ($date ?? today())->format('Ymd');
    }

    /** DB 保存用。例: 202604201 */
    public static function build(int $seq, ?CarbonInterface $date = null): string
    {
        return self::datePrefix($date).$seq;
    }

    /** 画面表示用（通番から）。例: 201 */
    public static function formatDisplay(int $seq, ?CarbonInterface $date = null): string
    {
        $day = (int) ($date ?? today())->format('j');

        return $day.$seq;
    }

    /** 保存値を表示用に変換。例: 202604201 → 201 */
    public static function formatStoredForDisplay(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }
        if (preg_match('/^\d{4}\d{2}(\d{2})(\d+)$/', $stored, $m) === 1) {
            return ((int) $m[1]).$m[2];
        }

        return $stored;
    }

    public static function parseSeq(?string $stored, ?CarbonInterface $date = null): ?int
    {
        if ($stored === null || $stored === '') {
            return null;
        }
        $prefix = self::datePrefix($date);
        if (str_starts_with($stored, $prefix)) {
            $suffix = substr($stored, strlen($prefix));
            if ($suffix !== '' && ctype_digit($suffix)) {
                return (int) $suffix;
            }
        }
        if (preg_match('/^\d{8}(\d+)$/', $stored, $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }
}
