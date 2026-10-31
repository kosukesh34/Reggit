<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminListQueryService;
use App\Services\Admin\MemberAdminQueryService;
use App\UseCases\Admin\Member\Data\AdminMemberListOutput;

class AdminMemberListUseCase
{
    private const PER_PAGE = 20;

    private MemberAdminQueryService $memberAdminService;

    public function __construct(MemberAdminQueryService $memberAdminService, private MemberAdminListQueryService $memberList)
    {
        $this->memberAdminService = $memberAdminService;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function handle(int $projectId, array $filters = []): AdminMemberListOutput
    {
        $members = $this->memberList->getPaginatedList($projectId, self::PER_PAGE, $filters);

        return new AdminMemberListOutput(
            $members,
            $this->memberAdminService->getRankOptionsForSelect($projectId),
            $this->memberList->segmentCriteria($filters) !== null,
        );
    }
}
