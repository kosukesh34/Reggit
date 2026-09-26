<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;
use App\Services\Admin\MemberBenefitService;

class AdminMemberIssueCouponUseCase
{
    private MemberAdminService $memberAdminService;

    public function __construct(
        MemberAdminService $memberAdminService,
        private MemberBenefitService $memberBenefitService,
    ) {
        $this->memberAdminService = $memberAdminService;
    }

    public function handle(int $projectId, int $memberId, int $couponId): void
    {
        $member = $this->memberAdminService->findForWrite($memberId, $projectId);
        $this->memberBenefitService->issueCouponToMember($member, $couponId);
    }
}
