<?php

namespace App\UseCases\Admin\Announcement;

use App\Models\Announcement;
use App\Services\Admin\AnnouncementAdminService;
use App\Services\PushNotificationService;

class AdminAnnouncementDeleteUseCase
{
    public function __construct(
        private AnnouncementAdminService $announcementAdminService,
        private PushNotificationService $pushNotificationService
    ) {}

    public function handle(int $id): void
    {
        $announcement = $this->announcementAdminService->findOrFail($id);

        if ($announcement->delivery_status === Announcement::DELIVERY_STATUS_SENT) {
            $this->pushNotificationService->cancelAnnouncementPush($announcement);
        }

        $this->announcementAdminService->delete($id);
    }
}
