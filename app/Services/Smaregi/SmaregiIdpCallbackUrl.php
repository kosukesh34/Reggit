<?php

namespace App\Services\Smaregi;

class SmaregiIdpCallbackUrl
{
    public static function token(): ?string
    {
        $token = config('smaregi.idp_callback_token');

        return is_string($token) && preg_match('/\A[0-9a-f]{64}\z/i', $token) === 1
            ? $token
            : null;
    }

    public static function forBaseUrl(string $baseUrl): ?string
    {
        $token = self::token();

        return $token === null ? null : rtrim($baseUrl, '/').'/api/smaregi/idp?callback_token='.$token;
    }
}
