<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;

class AdminMemberDeleteCouponUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService
    ) {}

    public function handle(int $projectId, int $memberId, int $memberCouponId): void
    {
        $member = $this->memberAdminService->findForWrite($memberId, $projectId);
        $this->memberAdminService->deleteMemberCoupon($member, $memberCouponId);
    }
}
