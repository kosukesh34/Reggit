<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\AnnouncementAdminService;
use App\Services\Admin\AnnouncementAudienceService;
use App\UseCases\Admin\Announcement\Data\AdminAnnouncementReadsOutput;

class AdminAnnouncementReadsUseCase
{
    public function __construct(
        private AnnouncementAdminService $announcementAdminService,
        private AnnouncementAudienceService $audience,
    ) {}

    public function handle(int $id): AdminAnnouncementReadsOutput
    {
        $announcement = $this->announcementAdminService->findOrFail($id);
        $recipients = $this->audience->getDeliveryRecipients($announcement);
        $readCount = collect($recipients)->whereNotNull('read_at')->count();
        $couponIssuedCount = collect($recipients)->whereIn('coupon_status', ['unused', 'used'])->count();
        $couponUsedCount = collect($recipients)->where('coupon_status', 'used')->count();

        return new AdminAnnouncementReadsOutput(
            $announcement,
            $recipients,
            count($recipients),
            $readCount,
            $couponIssuedCount,
            $couponUsedCount,
        );
    }
}
