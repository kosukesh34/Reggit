<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\CouponAdminService;
use App\UseCases\Admin\Coupon\Data\AdminCouponRedemptionsOutput;

class AdminCouponRedemptionsUseCase
{
    public function __construct(
        private CouponAdminService $couponAdminService,
    ) {}

    public function handle(int $projectId, int $couponId): AdminCouponRedemptionsOutput
    {
        $coupon = $this->couponAdminService->findOrFail($couponId, $projectId);
        $redemptions = $this->couponAdminService->getRedemptionsForCoupon($couponId, $projectId);

        return new AdminCouponRedemptionsOutput(
            $coupon,
            $redemptions,
            $redemptions->total(),
        );
    }
}
