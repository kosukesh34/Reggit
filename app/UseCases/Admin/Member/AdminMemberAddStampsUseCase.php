<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;

class AdminMemberAddStampsUseCase
{
    private MemberAdminService $memberAdminService;

    public function __construct(MemberAdminService $memberAdminService)
    {
        $this->memberAdminService = $memberAdminService;
    }

    public function handle(int $projectId, int $memberId, int $stampCardId, int $count): void
    {
        $member = $this->memberAdminService->findOrFail($memberId, $projectId);
        $this->memberAdminService->addStampsToMember($member, $stampCardId, $count);
    }
}
