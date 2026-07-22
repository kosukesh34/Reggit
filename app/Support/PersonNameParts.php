<?php

namespace App\Support;

/**
 * 氏名・カナを姓/名に分割・結合する（スマレジ lastName/firstName 対応）。
 */
class PersonNameParts
{
    /**
     * @return array{0: string, 1: string} [last, first]
     */
    public static function split(?string $full): array
    {
        $full = trim((string) $full);
        if ($full === '') {
            return ['', ''];
        }
        $parts = preg_split('/\s+/u', $full, 2);
        if (! is_array($parts)) {
            return [$full, ''];
        }

        return [
            (string) ($parts[0] ?? ''),
            (string) ($parts[1] ?? ''),
        ];
    }

    public static function join(?string $last, ?string $first): string
    {
        return trim(trim((string) $last).' '.trim((string) $first));
    }
}
