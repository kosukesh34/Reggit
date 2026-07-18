<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserDevice;

/** 会員の利用チャネル（LINE / ネイティブアプリ）判定。登録経路フラグではなく現在の利用状況で判定する。 */
final class MemberChannel
{
    public const LINE = 'line';

    public const APP = 'app';

    public const BOTH = 'both';

    public static function isValidLineUserId(?string $lineUserId): bool
    {
        if ($lineUserId === null || $lineUserId === '') {
            return false;
        }

        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }

    public static function hasLine(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return self::isValidLineUserId($user->line_user_id);
    }

    public static function hasApp(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->relationLoaded('devices')) {
            return $user->devices->isNotEmpty();
        }

        return UserDevice::query()->where('user_id', $user->id)->exists();
    }

    /**
     * @return list<string>
     */
    public static function channels(?User $user): array
    {
        $channels = [];
        if (self::hasLine($user)) {
            $channels[] = self::LINE;
        }
        if (self::hasApp($user)) {
            $channels[] = self::APP;
        }

        return $channels;
    }

    public static function label(?User $user): string
    {
        $channels = self::channels($user);
        if ($channels === []) {
            return '—';
        }

        $labels = [];
        if (in_array(self::LINE, $channels, true)) {
            $labels[] = 'LINE';
        }
        if (in_array(self::APP, $channels, true)) {
            $labels[] = 'アプリ';
        }

        return implode('・', $labels);
    }

    /** @return array<string, string> */
    public static function filterOptions(): array
    {
        return [
            self::LINE => 'LINE',
            self::APP => 'アプリ',
            self::BOTH => 'LINEとアプリの両方',
        ];
    }
}
