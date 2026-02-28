<?php

namespace App\UseCases\Admin\Announcement;

use App\Models\Announcement;
use App\Services\Admin\AnnouncementAdminService;

class AdminAnnouncementEditUseCase
{
    private AnnouncementAdminService $announcementAdminService;

    public function __construct(AnnouncementAdminService $announcementAdminService)
    {
        $this->announcementAdminService = $announcementAdminService;
    }

    public function handle(int $id): Announcement
    {
        return $this->announcementAdminService->findOrFail($id);
    }
}
