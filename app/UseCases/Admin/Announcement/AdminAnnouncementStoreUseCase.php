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

    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated): Announcement
    {
        $validated['is_pinned'] = (bool) ($validated['is_active'] ?? false);
        unset($validated['is_active']);
        if (! isset($validated['published_at']) || $validated['published_at'] === null || $validated['published_at'] === '') {
            $validated['published_at'] = now();
        }

        return $this->announcementAdminService->create($projectId, $validated);
    }
}
