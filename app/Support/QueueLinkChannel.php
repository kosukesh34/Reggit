<?php

namespace App\Support;

use App\Models\User;

/** 順番待ちエントリの通知連携チャネル（未登録 / LINE / アプリ）。 */
final class QueueLinkChannel
{
    public const NONE = 'none';

    public const LINE = MemberChannel::LINE;

    public const APP = MemberChannel::APP;

    public const BOTH = MemberChannel::BOTH;

    public static function resolve(?User $user, ?int $userId): string
    {
        if ($userId === null || $user === null) {
            return self::NONE;
        }

        $hasLine = MemberChannel::hasLine($user);
        $hasApp = MemberChannel::hasApp($user);

        if ($hasLine && $hasApp) {
            return self::BOTH;
        }
        if ($hasLine) {
            return self::LINE;
        }

        // user_id ありで LINE ID でも端末でもない場合はネイティブゲスト扱い
        return self::APP;
    }
}
