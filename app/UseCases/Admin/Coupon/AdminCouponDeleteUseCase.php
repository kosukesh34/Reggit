<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\CouponAdminService;

class AdminCouponDeleteUseCase
{
    private CouponAdminService $couponAdminService;

    public function __construct(CouponAdminService $couponAdminService)
    {
        $this->couponAdminService = $couponAdminService;
    }

    public function handle(int $couponId, int $projectId): void
    {
        $this->couponAdminService->delete($couponId, $projectId);
    }
}
