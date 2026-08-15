<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\Admin\AnnouncementAdminService;
use App\Services\AnnouncementNotificationService;
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

    public function handle(
        AnnouncementNotificationService $announcementNotificationService,
        AnnouncementAdminService $announcementAdminService,
    ): void {
        $announcement = Announcement::withoutGlobalScopes()->find($this->announcementId);
        if ($announcement === null || $announcement->delivery_status !== Announcement::DELIVERY_STATUS_SCHEDULED) {
            return;
        }

        $announcementAdminService->finalizeAudienceForSend($announcement);
        $announcement = $announcement->fresh() ?? $announcement;
        $announcementAdminService->issueDeliveryCouponIfSet($announcement);
        if ($announcement->send_notification !== false) {
            $announcementNotificationService->send($announcement);
        }

        $announcement->update([
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'published_at' => $announcement->published_at ?? now(),
            'sent_at' => now(),
        ]);
    }
}
