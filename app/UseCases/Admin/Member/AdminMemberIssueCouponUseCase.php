<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;

class AdminMemberIssueCouponUseCase
{
    private MemberAdminService $memberAdminService;

    public function __construct(MemberAdminService $memberAdminService)
    {
        $this->memberAdminService = $memberAdminService;
    }

    public function handle(int $projectId, int $memberId, int $couponId): void
    {
        $member = $this->memberAdminService->findForWrite($memberId, $projectId);
        $this->memberAdminService->issueCouponToMember($member, $couponId);
    }
}
