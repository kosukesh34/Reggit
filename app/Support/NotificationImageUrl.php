<?php

namespace App\Support;

/**
 * プッシュ通知・LINE Flex で使える画像 URL を正規化する。
 * LINE は HTTPS の公開 URL のみ受け付け、無効 URL があるとメッセージ全体が拒否される。
 */
class NotificationImageUrl
{
    /** プッシュ通知（OneSignal / FCM）向け。相対パスは APP_URL で絶対化する。 */
    public static function forPush(?string $imageUrl): ?string
    {
        foreach (self::expandCandidates($imageUrl) as $candidate) {
            $resolved = self::resolveHttpsPublicUrl($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /** LINE Flex の hero 画像向け。取得しやすい URL に正規化する。 */
    public static function forLine(?string $imageUrl): ?string
    {
        $resolved = self::forPush($imageUrl);
        if ($resolved === null) {
            return null;
        }

        return self::applyLineFetchHints($resolved);
    }

    /** @return list<string> */
    private static function expandCandidates(?string $imageUrl): array
    {
        $normalized = trim((string) $imageUrl);
        if ($normalized === '') {
            return [];
        }

        $candidates = [$normalized];
        $rebased = self::rebaseStorageUrlToCurrentApp($normalized);
        if ($rebased !== null && ! in_array($rebased, $candidates, true)) {
            $candidates[] = $rebased;
        }

        return $candidates;
    }

    private static function rebaseStorageUrlToCurrentApp(string $url): ?string
    {
        $path = self::extractPublicStoragePath($url);
        if ($path === null) {
            return null;
        }

        $base = rtrim(self::notificationImageBaseUrl(), '/');
        if ($base === '') {
            return null;
        }

        return $base.'/storage/'.$path;
    }

    private static function extractPublicStoragePath(string $url): ?string
    {
        if (preg_match('#/storage/(.+)$#', $url, $matches) === 1) {
            return $matches[1];
        }

        if (str_starts_with($url, 'storage/')) {
            return substr($url, strlen('storage/'));
        }

        return null;
    }

    private static function resolveHttpsPublicUrl(string $imageUrl): ?string
    {
        $absolute = self::toAbsoluteUrl($imageUrl);
        if ($absolute === null) {
            return null;
        }

        if (self::isHttpsPublicUrl($absolute)) {
            return $absolute;
        }

        if (preg_match('#^http://#i', $absolute) === 1) {
            $upgraded = preg_replace('#^http://#i', 'https://', $absolute);
            if (is_string($upgraded) && self::isHttpsPublicUrl($upgraded)) {
                return $upgraded;
            }
        }

        return null;
    }

    private static function notificationImageBaseUrl(): string
    {
        $override = trim((string) config('line.notification_image_base_url', ''));
        if ($override !== '') {
            return rtrim($override, '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    private static function toAbsoluteUrl(?string $imageUrl): ?string
    {
        $normalized = trim((string) $imageUrl);
        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '//')) {
            $normalized = 'https:'.$normalized;
        }

        if (str_starts_with($normalized, '/')) {
            $base = self::notificationImageBaseUrl();
            if ($base === '') {
                return null;
            }

            return $base.$normalized;
        }

        if (preg_match('#^https?://#i', $normalized) !== 1) {
            return null;
        }

        return $normalized;
    }

    private static function isHttpsPublicUrl(string $url): bool
    {
        if (preg_match('#^https://#i', $url) !== 1) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '[::1]'], true)) {
            return false;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return false;
        }

        return true;
    }

    /**
     * LINE サーバーが画像を取得しやすいよう URL を調整する。
     * ngrok 無料枠は警告 HTML を返すことがあるため、スキップ用クエリを付与する。
     */
    private static function applyLineFetchHints(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host !== '' && str_contains($host, 'ngrok')) {
            return self::appendQueryParam($url, 'ngrok-skip-browser-warning', '1');
        }

        return $url;
    }

    private static function appendQueryParam(string $url, string $key, string $value): string
    {
        if (str_contains($url, $key.'=')) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.rawurlencode($key).'='.rawurlencode($value);
    }
}
