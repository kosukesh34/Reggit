<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;
use App\Services\Admin\MemberBenefitService;

class AdminMemberDeleteCouponUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private MemberBenefitService $memberBenefitService,
    ) {}

    public function handle(int $projectId, int $memberId, int $memberCouponId): void
    {
        $member = $this->memberAdminService->findForWrite($memberId, $projectId);
        $this->memberBenefitService->deleteMemberCoupon($member, $memberCouponId);
    }
}
