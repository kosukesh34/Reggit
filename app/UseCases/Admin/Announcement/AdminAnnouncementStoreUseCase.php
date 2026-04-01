<?php

namespace App\UseCases\Admin\Announcement;

use App\Models\Announcement;
use App\Services\Admin\AnnouncementAdminService;

class AdminAnnouncementStoreUseCase
{
    private AnnouncementAdminService $announcementAdminService;

    public function __construct(AnnouncementAdminService $announcementAdminService)
    {
        $this->announcementAdminService = $announcementAdminService;
    }

    /** @param array<string, bool|int|float|string|array|object|null> $validated */
    public function handle(int $projectId, array $validated): Announcement
    {
        return $this->announcementAdminService->create($projectId, $validated);
    }
}
