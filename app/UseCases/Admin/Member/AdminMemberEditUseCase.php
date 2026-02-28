<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Member\Data\AdminMemberEditOutput;

class AdminMemberEditUseCase
{
    private MemberAdminService $memberAdminService;

    public function __construct(MemberAdminService $memberAdminService)
    {
        $this->memberAdminService = $memberAdminService;
    }

    public function handle(int $projectId, int $memberId): AdminMemberEditOutput
    {
        $member = $this->memberAdminService->findOrFail($memberId, $projectId);
        $rankOptions = $this->memberAdminService->getRankOptionsForSelect($projectId);

        return new AdminMemberEditOutput($member, $rankOptions);
    }
}
