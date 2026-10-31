<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

/** LINE チャネル・LIFF 接続とテーマ色の設定。 */
class LineSettingStore
{
    public static function getLineThemeColors(?int $projectId = null): array
    {
        $primary = ShopSettingStore::getValue('line_primary_color', '#0E7C66', $projectId);

        return [
            'primary' => $primary,
            'primary_rgb' => self::hexToRgb($primary),
            'primary_dark' => ShopSettingStore::getValue('line_primary_dark', '#0A6B58', $projectId),
            'success' => ShopSettingStore::getValue('line_success_color', '#059669', $projectId),
            'danger' => ShopSettingStore::getValue('line_danger_color', '#dc2626', $projectId),
        ];
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '0, 0, 0';
        }
        $r = (int) hexdec(substr($hex, 0, 2));
        $g = (int) hexdec(substr($hex, 2, 2));
        $b = (int) hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }

    public static function getLineLiffId(?int $projectId = null): string
    {
        $value = trim((string) ShopSettingStore::getValue('line_liff_id', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.liff_id', ''));
    }

    public static function setLineLiffId(?string $liffId, ?int $projectId = null): void
    {
        ShopSettingStore::setValue('line_liff_id', trim((string) ($liffId ?? '')), 'string', $projectId);
    }

    public static function getLineLiffUrl(?int $projectId = null): string
    {
        $value = trim((string) ShopSettingStore::getValue('line_liff_url', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.liff_url', ''));
    }

    public static function setLineLiffUrl(?string $liffUrl, ?int $projectId = null): void
    {
        ShopSettingStore::setValue('line_liff_url', trim((string) ($liffUrl ?? '')), 'string', $projectId);
    }

    public static function getLineChannelAccessToken(?int $projectId = null): string
    {
        $value = trim((string) ShopSettingStore::getValue('line_channel_access_token', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.channel_access_token', ''));
    }

    public static function setLineChannelAccessToken(?string $token, ?int $projectId = null): void
    {
        $trimmed = trim((string) ($token ?? ''));
        if ($trimmed === '') {
            return;
        }
        ShopSettingStore::setValue('line_channel_access_token', $trimmed, 'string', $projectId);
    }

    public static function getLineChannelSecret(?int $projectId = null): string
    {
        $value = trim((string) ShopSettingStore::getValue('line_channel_secret', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.channel_secret', ''));
    }

    public static function setLineChannelSecret(?string $secret, ?int $projectId = null): void
    {
        $trimmed = trim((string) ($secret ?? ''));
        if ($trimmed === '') {
            return;
        }
        ShopSettingStore::setValue('line_channel_secret', $trimmed, 'string', $projectId);
    }

    public static function hasLineChannelAccessToken(?int $projectId = null): bool
    {
        return self::getLineChannelAccessToken($projectId) !== '';
    }

    /** LINE公式アカウントの友だち追加URL（メッセージ・クーポン配布用）。例: https://line.me/R/ti/p/@xxx */
    public static function getLineFriendAddUrl(?int $projectId = null): string
    {
        return trim((string) ShopSettingStore::getValue('line_friend_add_url', '', $projectId));
    }

    public static function setLineFriendAddUrl(?string $url, ?int $projectId = null): void
    {
        ShopSettingStore::setValue('line_friend_add_url', trim((string) ($url ?? '')), 'string', $projectId);
    }
}
