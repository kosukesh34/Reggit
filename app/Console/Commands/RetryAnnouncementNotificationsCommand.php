<?php

namespace App\Console\Commands;

use App\Jobs\SendAnnouncementNotificationJob;
use App\Models\Announcement;
use Illuminate\Console\Command;

class RetryAnnouncementNotificationsCommand extends Command
{
    protected $signature = 'announcements:retry-notifications';

    protected $description = '未完了のお知らせ通知を再送する';

    public function handle(): int
    {
        $ids = Announcement::withoutGlobalScopes()
            ->where('delivery_status', Announcement::DELIVERY_STATUS_SENT)
            ->where('send_notification', true)
            ->where('notification_pending', true)
            ->where('notification_retry_at', '<=', now())
            ->orderBy('notification_retry_at')
            ->limit(100)
            ->pluck('id');

        foreach ($ids as $id) {
            try {
                SendAnnouncementNotificationJob::dispatch((int) $id);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return self::SUCCESS;
    }
}
