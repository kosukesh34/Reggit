<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\AnnouncementAdminService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminAnnouncementIndexUseCase
{
    private const PER_PAGE = 20;

    private AnnouncementAdminService $announcementAdminService;

    public function __construct(AnnouncementAdminService $announcementAdminService)
    {
        $this->announcementAdminService = $announcementAdminService;
    }

    public function handle(): LengthAwarePaginator
    {
        return $this->announcementAdminService->getPaginatedList(self::PER_PAGE);
    }
}
