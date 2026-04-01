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

    /** @param array<string, bool|int|float|string|array|object|null> $validated */
    public function handle(int $id, array $validated): void
    {
        $this->announcementAdminService->update($id, $validated);
    }
}
