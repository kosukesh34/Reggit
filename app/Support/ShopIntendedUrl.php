<?php

namespace App\Support;

use Illuminate\Http\Request;

/** ショップ認証後の戻り先（同一オリジンの相対パスのみ許可）。 */
final class ShopIntendedUrl
{
    private const SESSION_KEY = 'shop_auth_intended';

    public static function remember(Request $request, string $intended): void
    {
        $safe = self::sanitize($intended);
        if ($safe !== null) {
            $request->session()->put(self::SESSION_KEY, $safe);
        }
    }

    public static function pull(Request $request, string $fallback): string
    {
        $intended = $request->session()->pull(self::SESSION_KEY);
        if (! is_string($intended) || $intended === '') {
            return $fallback;
        }

        return $intended;
    }

    public static function sanitize(string $intended): ?string
    {
        $intended = trim($intended);
        if ($intended === '' || ! str_starts_with($intended, '/') || str_starts_with($intended, '//')) {
            return null;
        }
        if (str_contains($intended, "\0") || str_contains($intended, '\\')) {
            return null;
        }

        return $intended;
    }
}
