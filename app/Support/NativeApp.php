<?php

namespace App\Support;

/** ネイティブアプリ識別子（config/native_app.php / Skip.env と一致）。 */
final class NativeApp
{
    public static function userAgentToken(): string
    {
        return (string) config('native_app.user_agent_token', 'Reggit');
    }

    public static function userAgentPattern(): string
    {
        return '/'.preg_quote(self::userAgentToken(), '/').'\/\d+/i';
    }

    public static function urlScheme(): string
    {
        return (string) config('native_app.url_scheme', 'reggit');
    }

    public static function urlSchemePrefix(): string
    {
        return self::urlScheme().'://';
    }

    public static function orderTypeMobileLabel(): string
    {
        return (string) config('native_app.order_type_mobile_label', 'Reggit');
    }
}
