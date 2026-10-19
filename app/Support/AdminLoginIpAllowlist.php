<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\IpUtils;

/** 管理画面ログインの許可IP（空なら制限なし）。 */
final class AdminLoginIpAllowlist
{
    /**
     * @return list<string>
     */
    public static function parse(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/[\s,]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [];
        }

        return array_values(array_unique(array_map(static fn (string $part): string => trim($part), $parts)));
    }

    public static function isValidEntry(string $entry): bool
    {
        if (str_contains($entry, '/')) {
            [$ip, $mask] = array_pad(explode('/', $entry, 2), 2, null);
            if ($ip === null || $mask === null || ! ctype_digit($mask)) {
                return false;
            }
            $maskInt = (int) $mask;
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $maskInt >= 0 && $maskInt <= 32;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return $maskInt >= 0 && $maskInt <= 128;
            }

            return false;
        }

        return filter_var($entry, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * @param  list<string>  $entries
     */
    public static function allows(?string $ip, array $entries): bool
    {
        if ($entries === []) {
            return true;
        }
        if ($ip === null || $ip === '') {
            return false;
        }

        return IpUtils::checkIp($ip, $entries);
    }

    public static function normalizeForStorage(?string $raw): ?string
    {
        $entries = self::parse($raw);
        if ($entries === []) {
            return null;
        }

        return implode("\n", $entries);
    }
}
