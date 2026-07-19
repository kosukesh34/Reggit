<?php

namespace App\UseCases\Admin\Coupon;

use App\Enums\CouponDeliveryType;
use App\Models\Member;
use App\Services\Admin\CouponDeliveryTargetService;

class AdminCouponDeliveryPreviewUseCase
{
    public function __construct(
        private CouponDeliveryTargetService $couponDeliveryTargetService,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{count: int, total: int, is_event: bool}
     */
    public function handle(int $projectId, string $deliveryType, array $params): array
    {
        $total = Member::query()->where('project_id', $projectId)->count();
        $isEvent = CouponDeliveryType::isEventRankUpArrival($deliveryType);
        $count = $this->couponDeliveryTargetService->countTargets($projectId, $deliveryType, $params);

        return [
            'count' => $count,
            'total' => $total,
            'is_event' => $isEvent,
        ];
    }
}
