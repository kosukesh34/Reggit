<?php

namespace App\Http\Support;

use Illuminate\Http\Request;

/**
 * リクエストから公開オリジン（scheme + host）を解決する。
 */
final class RequestOrigin
{
    public static function fromRequest(Request $request): string
    {
        $forwardedHost = $request->header('X-Forwarded-Host') ?? $request->server('HTTP_X_FORWARDED_HOST');
        if (is_string($forwardedHost) && trim($forwardedHost) !== '') {
            $proto = $request->header('X-Forwarded-Proto') ?? $request->server('HTTP_X_FORWARDED_PROTO');
            $scheme = is_string($proto) && trim($proto) !== '' ? trim($proto) : $request->getScheme();
            $origin = $scheme.'://'.trim(explode(',', $forwardedHost)[0]);
        } else {
            $origin = $request->getSchemeAndHttpHost();
        }

        return self::ensureHttps(rtrim($origin, '/'));
    }

    public static function fromConfig(): string
    {
        return self::ensureHttps(rtrim((string) config('app.url', url('/')), '/'));
    }

    private static function ensureHttps(string $origin): string
    {
        $host = parse_url($origin, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return $origin;
        }
        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $origin;
        }
        if (str_starts_with($origin, 'http://')) {
            return 'https://'.substr($origin, 7);
        }

        return $origin;
    }
}
