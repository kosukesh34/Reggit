<?php

namespace App\UseCases\Admin\Coupon;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Services\Admin\MemberAdminService;
use App\Services\Smaregi\SmaregiCouponSyncService;
use App\UseCases\Admin\Coupon\Data\AdminCouponBulkIssueFormOutput;

class AdminCouponBulkIssueFormUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private SmaregiCouponSyncService $smaregiCouponSync,
    ) {}

    public function handle(int $projectId): AdminCouponBulkIssueFormOutput
    {
        $coupons = $this->memberAdminService->getActiveCouponsForSelect($projectId);
        $memberCount = Member::where('project_id', $projectId)->count();
        $linkedCount = $coupons->filter(
            fn ($coupon) => $coupon->smaregi_coupon_id !== null && $coupon->smaregi_coupon_id !== ''
        )->count();

        $scheduledDeliveries = CouponDelivery::query()
            ->where('project_id', $projectId)
            ->where('status', CouponDeliveryStatus::SCHEDULED)
            ->with('coupon')
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();

        return new AdminCouponBulkIssueFormOutput(
            $coupons,
            $memberCount,
            $this->smaregiCouponSync->hasSmaregiContract($projectId),
            $linkedCount,
            $coupons->count() - $linkedCount,
            $scheduledDeliveries,
            CouponDeliveryType::getValues(),
            CouponDeliveryType::formGroups(),
            CouponDeliveryType::jsConfig(),
        );
    }
}
