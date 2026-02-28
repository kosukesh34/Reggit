<?php

namespace App\UseCases\Admin\Coupon;

use App\Services\Admin\MemberAdminService;

class AdminCouponBulkIssueUseCase
{
    private MemberAdminService $memberAdminService;

    public function __construct(MemberAdminService $memberAdminService)
    {
        $this->memberAdminService = $memberAdminService;
    }

    /** @param array<int, int> $memberIds 空の場合は全員に配布 */
    public function handle(int $projectId, int $couponId, array $memberIds): int
    {
        return $this->memberAdminService->issueCouponToMembers($projectId, $couponId, $memberIds);
    }
}
