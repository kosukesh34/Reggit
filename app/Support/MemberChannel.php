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

    public const PLATFORM_IOS = 'ios';

    public const PLATFORM_ANDROID = 'android';

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
    public static function platforms(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        if ($user->relationLoaded('devices')) {
            $values = $user->devices->pluck('platform')->all();
        } else {
            $values = UserDevice::query()
                ->where('user_id', $user->id)
                ->pluck('platform')
                ->all();
        }

        $platforms = [];
        foreach ($values as $platform) {
            if ($platform !== self::PLATFORM_IOS && $platform !== self::PLATFORM_ANDROID) {
                continue;
            }
            if (! in_array($platform, $platforms, true)) {
                $platforms[] = $platform;
            }
        }

        return $platforms;
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
        $labels = [];
        if (self::hasLine($user)) {
            $labels[] = 'LINE';
        }

        $platforms = self::platforms($user);
        if (in_array(self::PLATFORM_IOS, $platforms, true)) {
            $labels[] = 'iOS';
        }
        if (in_array(self::PLATFORM_ANDROID, $platforms, true)) {
            $labels[] = 'Android';
        }
        if (self::hasApp($user) && $platforms === []) {
            $labels[] = 'アプリ';
        }

        if ($labels === []) {
            return '—';
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
