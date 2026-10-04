<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\AnnouncementNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAnnouncementNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 120, 300, 600];

    public int $timeout = 60;

    public function __construct(public int $announcementId) {}

    public function handle(AnnouncementNotificationService $notifications): void
    {
        // Persist a lease before network I/O. A stopped worker becomes retryable
        // after five minutes, including when no queue message survives.
        $claimed = Announcement::withoutGlobalScopes()
            ->whereKey($this->announcementId)
            ->where('delivery_status', Announcement::DELIVERY_STATUS_SENT)
            ->where('send_notification', true)
            ->where('notification_pending', true)
            ->where('notification_retry_at', '<=', now())
            ->update(['notification_retry_at' => now()->addMinutes(5)]);
        if ($claimed === 0) {
            return;
        }

        $announcement = Announcement::withoutGlobalScopes()->find($this->announcementId);
        if ($announcement === null) {
            return;
        }

        try {
            $result = $notifications->send($announcement);
            if (($result['failed'] ?? 0) > 0 || ($result['line_failed'] ?? 0) > 0) {
                throw new \RuntimeException('Announcement notification delivery is incomplete.');
            }
        } catch (\Throwable $exception) {
            $announcement->update(['notification_retry_at' => now()->addMinute()]);
            throw $exception;
        }

        $announcement->update([
            'notification_pending' => false,
            'notification_retry_at' => null,
        ]);
    }
}
