<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\CouponAdminService;
use App\UseCases\Admin\Coupon\Data\AdminCouponIndexOutput;

class AdminCouponIndexUseCase
{
    private const PER_PAGE = 20;

    private CouponAdminService $couponAdminService;

    public function __construct(CouponAdminService $couponAdminService)
    {
        $this->couponAdminService = $couponAdminService;
    }

    public function handle(int $projectId): AdminCouponIndexOutput
    {
        $coupons = $this->couponAdminService->getPaginatedList($projectId, self::PER_PAGE);

        return new AdminCouponIndexOutput($coupons);
    }
}
