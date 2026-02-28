<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\CouponAdminService;
use App\UseCases\Admin\Coupon\Data\AdminCouponFormOutput;

class AdminCouponEditFormUseCase
{
    private CouponAdminService $couponAdminService;

    public function __construct(CouponAdminService $couponAdminService)
    {
        $this->couponAdminService = $couponAdminService;
    }

    public function handle(int $projectId, int $couponId): AdminCouponFormOutput
    {
        $coupon = $this->couponAdminService->findOrFail($couponId, $projectId);
        $stampCards = $this->couponAdminService->getActiveStampCardsForSelect($projectId);

        return new AdminCouponFormOutput($coupon, $stampCards);
    }
}
