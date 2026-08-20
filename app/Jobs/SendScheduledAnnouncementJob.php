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
use Illuminate\Support\Facades\DB;

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
        $shouldNotify = false;
        $announcement = DB::transaction(function () use ($announcementAdminService, &$shouldNotify): ?Announcement {
            $locked = Announcement::withoutGlobalScopes()
                ->whereKey($this->announcementId)
                ->lockForUpdate()
                ->first();
            if ($locked === null || $locked->delivery_status !== Announcement::DELIVERY_STATUS_SCHEDULED) {
                return null;
            }

            $announcementAdminService->finalizeAudienceForSend($locked);
            $locked = $locked->fresh() ?? $locked;
            $announcementAdminService->issueDeliveryCouponIfSet($locked);
            $shouldNotify = $locked->send_notification !== false;
            $locked->update([
                'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
                'published_at' => $locked->published_at ?? now(),
                'sent_at' => now(),
            ]);

            return $locked->fresh() ?? $locked;
        });
        if ($announcement === null || $shouldNotify !== true) {
            return;
        }

        $announcementNotificationService->send($announcement);
    }
}
