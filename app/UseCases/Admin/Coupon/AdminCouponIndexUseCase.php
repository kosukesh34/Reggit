<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\CouponAdminService;
use App\Services\Admin\CouponDeliveryHistoryService;
use App\UseCases\Admin\Coupon\Data\AdminCouponIndexOutput;

class AdminCouponIndexUseCase
{
    private const PER_PAGE = 20;

    public function __construct(
        private CouponAdminService $couponAdminService,
        private CouponDeliveryHistoryService $deliveryHistoryService,
    ) {}

    public function handle(int $projectId): AdminCouponIndexOutput
    {
        return new AdminCouponIndexOutput(
            $this->couponAdminService->getPaginatedList($projectId, self::PER_PAGE),
            $this->deliveryHistoryService->listForProject($projectId),
        );
    }
}
