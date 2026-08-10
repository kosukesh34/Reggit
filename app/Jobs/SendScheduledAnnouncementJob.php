<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\Admin\MemberAdminService;
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

    public function handle(AnnouncementNotificationService $announcementNotificationService, MemberAdminService $memberAdminService): void
    {
        $announcement = Announcement::withoutGlobalScopes()->find($this->announcementId);
        if (! $announcement || $announcement->delivery_status !== Announcement::DELIVERY_STATUS_SCHEDULED) {
            return;
        }

        $announcementNotificationService->send($announcement);

        if ($announcement->delivery_coupon_id) {
            $targetMemberIds = is_array($announcement->target_member_ids) && $announcement->target_member_ids !== []
                ? $announcement->target_member_ids
                : [];
            $isTargeted = in_array($announcement->delivery_type, [
                Announcement::DELIVERY_TYPE_PERSONAL,
                Announcement::DELIVERY_TYPE_SEGMENT,
                Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
            ], true);
            if ($isTargeted === false || $targetMemberIds !== []) {
                $memberAdminService->issueCouponToMembers(
                    (int) $announcement->project_id,
                    (int) $announcement->delivery_coupon_id,
                    $targetMemberIds
                );
            }
        }

        $announcement->update([
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'published_at' => $announcement->published_at ?? now(),
            'sent_at' => now(),
        ]);
    }
}
