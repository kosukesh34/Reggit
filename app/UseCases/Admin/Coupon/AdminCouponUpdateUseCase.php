<?php

namespace App\UseCases\Admin\Coupon;

use App\Models\Coupon;
use App\Services\Admin\CouponAdminService;

class AdminCouponUpdateUseCase
{
    private CouponAdminService $couponAdminService;

    public function __construct(CouponAdminService $couponAdminService)
    {
        $this->couponAdminService = $couponAdminService;
    }

    /** @param array<string, mixed> $validated */
    public function handle(int $couponId, array $validated, int $projectId): Coupon
    {
        return $this->couponAdminService->update($couponId, $validated, $projectId);
    }
}
