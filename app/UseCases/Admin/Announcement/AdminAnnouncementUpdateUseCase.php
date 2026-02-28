<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\AnnouncementAdminService;

class AdminAnnouncementUpdateUseCase
{
    private AnnouncementAdminService $announcementAdminService;

    public function __construct(AnnouncementAdminService $announcementAdminService)
    {
        $this->announcementAdminService = $announcementAdminService;
    }

    /** @param array<string, mixed> $validated */
    public function handle(int $id, array $validated): void
    {
        $this->announcementAdminService->update($id, $validated);
    }
}
