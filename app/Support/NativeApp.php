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
        $token = preg_quote(self::userAgentToken(), '/');

        // 旧ビルド（MobileOrderApp）との互換。全端末更新後に削除可。
        return '/(?:'.$token.'|MobileOrderApp)\/\d+/i';
    }

    /** @return list<string> */
    public static function allowedUrlSchemePrefixes(): array
    {
        return array_values(array_unique([
            self::urlSchemePrefix(),
            'mobileorderapp://',
        ]));
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
