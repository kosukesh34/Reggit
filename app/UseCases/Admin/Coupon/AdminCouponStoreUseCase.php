<?php

namespace App\UseCases\Admin\Coupon;

use App\Models\Coupon;
use App\Services\Admin\CouponAdminService;

class AdminCouponStoreUseCase
{
    private CouponAdminService $couponAdminService;

    public function __construct(CouponAdminService $couponAdminService)
    {
        $this->couponAdminService = $couponAdminService;
    }

    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated): Coupon
    {
        return $this->couponAdminService->create($projectId, $validated);
    }
}
