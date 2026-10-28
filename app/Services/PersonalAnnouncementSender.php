<?php

namespace App\Services;

use App\Jobs\SendAnnouncementNotificationJob;
use App\Models\Announcement;

/**
 * 会員1人宛てのお知らせを作り、アプリ通知と LINE で送る。
 *
 * 通知は既存の再送の仕組み（notification_pending と announcements:retry-notifications）に任せる。
 * 送信に失敗しても同じお知らせを作り直さないので、二重には届かない。
 */
class PersonalAnnouncementSender
{
    /** @param string|null $actionPath お知らせから開くアプリ内パス（例: /reservation） */
    public function send(int $projectId, int $memberId, string $title, string $body, ?string $actionPath = null, ?string $actionLabel = null): void
    {
        $announcement = Announcement::create([
            'project_id' => $projectId,
            'title' => $title,
            'body' => $body,
            'action_path' => $actionPath,
            'action_label' => $actionLabel,
            'is_pinned' => false,
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [$memberId],
            'published_at' => now(),
            'sent_at' => now(),
            'send_notification' => true,
            'notification_pending' => true,
            'notification_retry_at' => now(),
        ]);
        try {
            SendAnnouncementNotificationJob::dispatch((int) $announcement->id);
        } catch (\Throwable $exception) {
            // お知らせは作れているので、通知は再送の仕組みで送り直す
            report($exception);
        }
    }
}
