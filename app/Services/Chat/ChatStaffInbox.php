<?php

namespace App\Services\Chat;

use App\Models\Member;
use Illuminate\Support\Facades\Cache;

/**
 * 店舗がまだ読んでいない会話（会員が最後に送った会話）。管理画面のメニューの数字に使う。
 * メッセージは Firestore にあり Laravel は本文を受け取らないため、会員の画面から「送った」通知だけを受けて印を付ける。
 */
class ChatStaffInbox
{
    private const TTL_DAYS = 30;

    public function markUnread(int $projectId, int $memberId): void
    {
        $this->update($projectId, function (array $ids) use ($memberId): array {
            $ids[$memberId] = true;

            return $ids;
        });
    }

    public function markRead(int $projectId, int $memberId): void
    {
        $this->update($projectId, function (array $ids) use ($memberId): array {
            unset($ids[$memberId]);

            return $ids;
        });
    }

    /** 管理者が見られる会員（StoreAccessScope）の分だけ数える */
    public function unreadCount(int $projectId): int
    {
        $ids = array_keys(Cache::get($this->key($projectId), []));

        return $ids === [] ? 0 : Member::query()->where('project_id', $projectId)->whereIn('id', $ids)->count();
    }

    /** @param callable(array<int, true>): array<int, true> $change */
    private function update(int $projectId, callable $change): void
    {
        // 同時に送られても印が消えないよう、読み書きを直列にする
        Cache::lock("{$this->key($projectId)}:lock", 5)->block(3, function () use ($projectId, $change): void {
            Cache::put($this->key($projectId), $change(Cache::get($this->key($projectId), [])), now()->addDays(self::TTL_DAYS));
        });
    }

    private function key(int $projectId): string
    {
        return "chat-staff-unread:{$projectId}";
    }
}
