<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendScheduledAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $announcementId
    ) {}

    public function handle(PushNotificationService $pushNotificationService): void
    {
        $announcement = Announcement::withoutGlobalScopes()->find($this->announcementId);
        if (! $announcement || $announcement->delivery_status !== Announcement::DELIVERY_STATUS_SCHEDULED) {
            return;
        }

        $pushNotificationService->sendAnnouncement($announcement);

        $announcement->update([
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'published_at' => $announcement->published_at ?? now(),
            'sent_at' => now(),
        ]);
    }
}
