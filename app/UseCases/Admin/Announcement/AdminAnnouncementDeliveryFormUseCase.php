<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Announcement\Data\AdminAnnouncementDeliveryFormView;

class AdminAnnouncementDeliveryFormUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
    ) {}

    public function handle(int $projectId): AdminAnnouncementDeliveryFormView
    {
        return new AdminAnnouncementDeliveryFormView(
            $this->memberAdminService->getCountByProject($projectId),
            $this->memberAdminService->getActiveCouponsForSelect($projectId),
        );
    }
}
