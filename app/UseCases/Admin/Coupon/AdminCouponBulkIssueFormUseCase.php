<?php

namespace App\UseCases\Admin\Coupon;

use App\Models\Member;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Coupon\Data\AdminCouponBulkIssueFormOutput;

class AdminCouponBulkIssueFormUseCase
{
    private CouponAdminService $couponAdminService;

    private MemberAdminService $memberAdminService;

    public function __construct(
        CouponAdminService $couponAdminService,
        MemberAdminService $memberAdminService
    ) {
        $this->couponAdminService = $couponAdminService;
        $this->memberAdminService = $memberAdminService;
    }

    public function handle(int $projectId): AdminCouponBulkIssueFormOutput
    {
        $coupons = $this->memberAdminService->getActiveCouponsForSelect($projectId);
        $memberCount = Member::where('project_id', $projectId)->count();

        return new AdminCouponBulkIssueFormOutput($coupons, $memberCount);
    }
}
