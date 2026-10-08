<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\Admin\AnnouncementAdminService;
use App\Services\AnnouncementNotificationService;
use App\Services\AnnouncementStoreAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $announcement = DB::transaction(function () use ($announcementAdminService): ?Announcement {
            $locked = Announcement::withoutGlobalScopes()
                ->whereKey($this->announcementId)
                ->lockForUpdate()
                ->first();
            if ($locked === null || $locked->delivery_status !== Announcement::DELIVERY_STATUS_SCHEDULED) {
                return null;
            }

            if (! app(AnnouncementStoreAudience::class)->authorizedForSend($locked)) {
                $locked->update(['delivery_status' => 'held']);
                Log::warning('配信を保留しました。作成者の店舗権限を確認してください。', ['announcement_id' => $locked->id]);

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
                'notification_pending' => $shouldNotify,
                'notification_retry_at' => $shouldNotify ? now() : null,
            ]);

            return $locked->fresh() ?? $locked;
        });
        if ($announcement === null || ! $announcement->notification_pending) {
            return;
        }

        (new SendAnnouncementNotificationJob((int) $announcement->id))->handle($announcementNotificationService);
    }
}
