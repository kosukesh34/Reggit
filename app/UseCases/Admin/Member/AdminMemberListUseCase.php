<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Member\Data\AdminMemberListOutput;

class AdminMemberListUseCase
{
    private const PER_PAGE = 20;

    private MemberAdminService $memberAdminService;

    public function __construct(MemberAdminService $memberAdminService)
    {
        $this->memberAdminService = $memberAdminService;
    }

    public function handle(int $projectId, array $filters = []): AdminMemberListOutput
    {
        $members = $this->memberAdminService->getPaginatedList($projectId, self::PER_PAGE, $filters);

        return new AdminMemberListOutput($members);
    }
}
