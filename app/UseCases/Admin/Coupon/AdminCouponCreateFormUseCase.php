<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\CouponAdminService;
use App\UseCases\Admin\Coupon\Data\AdminCouponFormOutput;

class AdminCouponCreateFormUseCase
{
    private CouponAdminService $couponAdminService;

    public function __construct(CouponAdminService $couponAdminService)
    {
        $this->couponAdminService = $couponAdminService;
    }

    public function handle(int $projectId): AdminCouponFormOutput
    {
        $stampCards = $this->couponAdminService->getActiveStampCardsForSelect($projectId);

        return new AdminCouponFormOutput(null, $stampCards);
    }
}
