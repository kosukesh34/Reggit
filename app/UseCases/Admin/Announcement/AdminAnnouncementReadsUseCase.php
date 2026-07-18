<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\AnnouncementAdminService;
use App\UseCases\Admin\Announcement\Data\AdminAnnouncementReadsOutput;

class AdminAnnouncementReadsUseCase
{
    public function __construct(
        private AnnouncementAdminService $announcementAdminService,
    ) {}

    public function handle(int $id): AdminAnnouncementReadsOutput
    {
        $announcement = $this->announcementAdminService->findOrFail($id);
        $reads = $this->announcementAdminService->getReadsForAnnouncement($id);

        return new AdminAnnouncementReadsOutput(
            $announcement,
            $reads,
            count($reads),
        );
    }
}
