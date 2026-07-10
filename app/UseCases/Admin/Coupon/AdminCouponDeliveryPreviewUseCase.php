<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\CouponDeliveryTargetService;

class AdminCouponDeliveryPreviewUseCase
{
    public function __construct(
        private CouponDeliveryTargetService $couponDeliveryTargetService,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function handle(int $projectId, string $deliveryType, array $params): int
    {
        return $this->couponDeliveryTargetService->countTargets($projectId, $deliveryType, $params);
    }
}
