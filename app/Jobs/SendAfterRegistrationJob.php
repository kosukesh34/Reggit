<?php

namespace App\Jobs;

use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\Member;
use App\Services\Admin\MemberAdminService;
use App\Services\AnnouncementNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAfterRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $memberId,
        public int $afterRegistrationDeliveryId
    ) {}

    public function handle(AnnouncementNotificationService $announcementNotificationService, MemberAdminService $memberAdminService): void
    {
        $member = Member::find($this->memberId);
        $config = AfterRegistrationDelivery::find($this->afterRegistrationDeliveryId);

        if (! $member || ! $config || ! $config->is_active) {
            return;
        }

        if ($member->project_id !== $config->project_id) {
            return;
        }

        $announcement = Announcement::create([
            'project_id' => $member->project_id,
            'title' => $config->title,
            'body' => $config->body,
            'is_pinned' => false,
            'delivery_type' => Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'published_at' => now(),
            'sent_at' => now(),
            'target_member_ids' => [$member->id],
        ]);

        $announcementNotificationService->send($announcement);

        if ($config->coupon_id) {
            $memberAdminService->issueCouponToMember($member, (int) $config->coupon_id);
        }
    }
}
