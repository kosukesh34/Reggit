<?php

namespace App\Services\Chat;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Models\Member;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use App\Support\LiffTabUrlBuilder;
use App\Support\MemberChannel;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Cache;

/**
 * 店舗からの返信を会員へ知らせる。本文はロック画面や LINE のトーク履歴に残さないよう載せない。
 */
class ChatReplyNotifier
{
    private const TITLE = 'お店からメッセージが届きました';

    /** 会員がチャットを開くまでに届いた返信の数。アプリアイコンの数字に使う */
    public static function unreadKey(int $projectId, int $memberId): string
    {
        return "chat-unread:{$projectId}:{$memberId}";
    }

    public function __construct(
        private PushNotificationService $push,
        private LineService $line,
        private LineConnectionSettings $lineSettings,
    ) {}

    public function notify(Member $member, string $projectKey): void
    {
        $projectId = (int) $member->project_id;
        Cache::add(self::unreadKey($projectId, (int) $member->id), 0, now()->addDays(30));
        $unread = (int) Cache::increment(self::unreadKey($projectId, (int) $member->id));
        // 続けて返信しても通知は会員ごとに1分1回にまとめる
        if (! Cache::add("chat-reply-notified:{$projectId}:{$member->id}", true, 60)) {
            return;
        }

        // 返信自体は Firestore に保存済みのため、通知の失敗は記録だけにする
        try {
            if ($member->user_id !== null) {
                $this->push->sendToUser($projectId, (int) $member->user_id, self::TITLE, 'チャットを開いてご確認ください', [
                    'type' => 'chat_reply',
                    'tab' => 'chat',
                    'project_id' => (string) $projectId,
                ], "chat-{$projectId}-{$member->id}", $unread);
            }
        } catch (\Throwable $e) {
            LogHelper::error('[Chat] native push failed', ['member_id' => $member->id, 'project_id' => $projectId, 'error' => $e->getMessage()]);
        }

        $lineUserId = trim((string) ($member->user?->line_user_id ?? ''));
        if ($lineUserId === '' || ! MemberChannel::isValidLineUserId($lineUserId) || ! $this->lineSettings->hasLineChannelAccessToken($projectId)) {
            return;
        }
        try {
            $this->line->pushMessage($lineUserId, [
                $this->line->createTextMessage(self::TITLE."\n".LiffTabUrlBuilder::build($projectId, $projectKey, 'chat')),
            ], $this->lineSettings->getLineChannelAccessToken($projectId));
        } catch (\Throwable $e) {
            LogHelper::error('[Chat] LINE push failed', ['member_id' => $member->id, 'project_id' => $projectId, 'error' => $e->getMessage()]);
        }
    }
}
