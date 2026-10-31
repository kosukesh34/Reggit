<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

/** LINE チャネル・LIFF 接続とテーマ色の設定。 */
class LineSettingStore
{
    public function __construct(
        private ShopSettingStore $values,
    ) {}

    public function getLineThemeColors(?int $projectId = null): array
    {
        $primary = $this->values->getValue('line_primary_color', '#0E7C66', $projectId);

        return [
            'primary' => $primary,
            'primary_rgb' => $this->hexToRgb($primary),
            'primary_dark' => $this->values->getValue('line_primary_dark', '#0A6B58', $projectId),
            'success' => $this->values->getValue('line_success_color', '#059669', $projectId),
            'danger' => $this->values->getValue('line_danger_color', '#dc2626', $projectId),
        ];
    }

    private function hexToRgb(string $hex): string
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

    public function getLineLiffId(?int $projectId = null): string
    {
        $value = trim((string) $this->values->getValue('line_liff_id', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.liff_id', ''));
    }

    public function setLineLiffId(?string $liffId, ?int $projectId = null): void
    {
        $this->values->setValue('line_liff_id', trim((string) ($liffId ?? '')), 'string', $projectId);
    }

    public function getLineLiffUrl(?int $projectId = null): string
    {
        $value = trim((string) $this->values->getValue('line_liff_url', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.liff_url', ''));
    }

    public function setLineLiffUrl(?string $liffUrl, ?int $projectId = null): void
    {
        $this->values->setValue('line_liff_url', trim((string) ($liffUrl ?? '')), 'string', $projectId);
    }

    public function getLineChannelAccessToken(?int $projectId = null): string
    {
        $value = trim((string) $this->values->getValue('line_channel_access_token', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.channel_access_token', ''));
    }

    public function setLineChannelAccessToken(?string $token, ?int $projectId = null): void
    {
        $trimmed = trim((string) ($token ?? ''));
        if ($trimmed === '') {
            return;
        }
        $this->values->setValue('line_channel_access_token', $trimmed, 'string', $projectId);
    }

    public function getLineChannelSecret(?int $projectId = null): string
    {
        $value = trim((string) $this->values->getValue('line_channel_secret', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.channel_secret', ''));
    }

    public function setLineChannelSecret(?string $secret, ?int $projectId = null): void
    {
        $trimmed = trim((string) ($secret ?? ''));
        if ($trimmed === '') {
            return;
        }
        $this->values->setValue('line_channel_secret', $trimmed, 'string', $projectId);
    }

    public function hasLineChannelAccessToken(?int $projectId = null): bool
    {
        return $this->getLineChannelAccessToken($projectId) !== '';
    }

    /** LINE公式アカウントの友だち追加URL（メッセージ・クーポン配布用）。例: https://line.me/R/ti/p/@xxx */
    public function getLineFriendAddUrl(?int $projectId = null): string
    {
        return trim((string) $this->values->getValue('line_friend_add_url', '', $projectId));
    }

    public function setLineFriendAddUrl(?string $url, ?int $projectId = null): void
    {
        $this->values->setValue('line_friend_add_url', trim((string) ($url ?? '')), 'string', $projectId);
    }
}
