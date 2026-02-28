<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\CouponAdminService;

class AdminCouponUpdateUseCase
{
    private CouponAdminService $couponAdminService;

    public function __construct(CouponAdminService $couponAdminService)
    {
        $this->couponAdminService = $couponAdminService;
    }

    /** @param array<string, mixed> $validated */
    public function handle(int $couponId, array $validated, int $projectId): void
    {
        $this->couponAdminService->update($couponId, $validated, $projectId);
    }
}
