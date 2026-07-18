<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Member\Data\AdminMemberDetailOutput;

class AdminMemberDetailUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService
    ) {}

    public function handle(int $projectId, int $memberId): AdminMemberDetailOutput
    {
        $member = $this->memberAdminService->findOrFail($memberId, $projectId);
        $purchaseSummary = $this->memberAdminService->getPurchaseSummary($member);

        return new AdminMemberDetailOutput($member, $purchaseSummary);
    }
}
