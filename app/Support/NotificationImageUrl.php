<?php

namespace App\Support;

/**
 * プッシュ通知・LINE Flex で使える画像 URL を正規化する。
 * LINE は HTTPS の公開 URL のみ受け付け、無効 URL があるとメッセージ全体が拒否される。
 */
class NotificationImageUrl
{
    /** プッシュ通知（FCM）向け。相対パスは APP_URL で絶対化する。 */
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

        $rebased = self::rebaseStorageUrlToCurrentApp($normalized);
        if ($rebased === null || $rebased === $normalized) {
            return [$normalized];
        }

        // LINE_NOTIFICATION_IMAGE_BASE_URL があるときは rebase を優先（DBに残った旧公開URLを差し替える）
        if (trim((string) config('line.notification_image_base_url', '')) !== '') {
            return [$rebased, $normalized];
        }

        return [$normalized, $rebased];
    }

    private static function rebaseStorageUrlToCurrentApp(string $url): ?string
    {
        if (! self::shouldRebaseStorageUrl($url)) {
            return null;
        }

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

    /**
     * 外部 CDN など、すでに公開ホスト上の storage URL は書き換えない。
     * 相対パス・非公開ホスト・ngrok・APP_URL 同一ホストのみ差し替える。
     */
    private static function shouldRebaseStorageUrl(string $url): bool
    {
        if (preg_match('#^https?://#i', $url) !== 1 && ! str_starts_with($url, '//')) {
            return true;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return true;
        }

        if (self::isNonPublicHost($host) || str_contains($host, 'ngrok')) {
            return true;
        }

        $appHost = strtolower((string) parse_url(rtrim((string) config('app.url'), '/'), PHP_URL_HOST));

        return $appHost !== '' && $host === $appHost;
    }

    private static function extractPublicStoragePath(string $url): ?string
    {
        $path = $url;
        if (preg_match('#^https?://#i', $url) === 1 || str_starts_with($url, '//')) {
            $parsedPath = parse_url($url, PHP_URL_PATH);
            if (! is_string($parsedPath) || $parsedPath === '') {
                return null;
            }
            $path = $parsedPath;
        }

        if (preg_match('#/storage/(.+)$#', $path, $matches) === 1) {
            return $matches[1];
        }

        if (str_starts_with($path, 'storage/')) {
            return substr($path, strlen('storage/'));
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
        if ($host === '' || self::isNonPublicHost($host)) {
            return false;
        }

        return true;
    }

    private static function isNonPublicHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '[::1]', '::1'], true)) {
            return true;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return true;
        }

        $ip = $host;
        if (str_starts_with($ip, '[') && str_ends_with($ip, ']')) {
            $ip = substr($ip, 1, -1);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        return false;
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
