<?php

namespace App\UseCases\Admin\Announcement;

use App\Models\Product;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Announcement\Data\AdminAnnouncementDeliveryFormView;

class AdminAnnouncementDeliveryFormUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
    ) {}

    public function handle(int $projectId): AdminAnnouncementDeliveryFormView
    {
        $products = Product::query()
            ->where('project_id', $projectId)
            ->orderBy('name')
            ->get(['id', 'name', 'smaregi_product_id']);

        return new AdminAnnouncementDeliveryFormView(
            $this->memberAdminService->getCountByProject($projectId),
            $this->memberAdminService->getActiveCouponsForSelect($projectId),
            $products,
        );
    }
}
