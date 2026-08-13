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

    public static function resolve(?User $user, ?int $userId = null): string
    {
        // user が取れれば連携済み（member 経由の旧データで user_id が null のケースも含む）
        if ($user === null) {
            return self::NONE;
        }
        if ($userId === null && ! MemberChannel::hasLine($user) && ! MemberChannel::hasApp($user)) {
            // 紐付け先ユーザーはあるがチャネル判定材料がない店頭ゲスト等
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

        // user ありで LINE でなければネイティブ（アプリ）連携
        return self::APP;
    }
}
