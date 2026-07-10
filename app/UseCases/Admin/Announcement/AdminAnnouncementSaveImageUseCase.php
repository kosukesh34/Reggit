<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\AnnouncementAdminService;

class AdminAnnouncementSaveImageUseCase
{
    public function __construct(
        private AnnouncementAdminService $announcementAdminService,
    ) {}

    public function handle(mixed $imageFile, int $projectId): string
    {
        return $this->announcementAdminService->saveAnnouncementImage($imageFile, $projectId);
    }
}
