<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\AnnouncementAdminService;
use App\Services\PushNotificationService;
use App\Util\LogHelper;

class AdminAnnouncementDeleteUseCase
{
    public function __construct(
        private AnnouncementAdminService $announcementAdminService,
        private PushNotificationService $pushNotificationService,
    ) {}

    public function handle(int $id): void
    {
        $announcement = $this->announcementAdminService->findOrFail($id);

        try {
            $this->pushNotificationService->cancelAnnouncementPush($announcement);
        } catch (\Throwable $e) {
            LogHelper::warning('[Push] cancelAnnouncementPush failed on delete', [
                'announcement_id' => $announcement->id,
                'project_id' => $announcement->project_id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->announcementAdminService->delete($id);
    }
}
