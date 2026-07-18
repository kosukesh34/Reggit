<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;

class AdminMemberAdjustStampsUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService
    ) {}

    public function handle(int $projectId, int $memberId, int $stampCardId, int $delta, ?string $description = null): void
    {
        $member = $this->memberAdminService->findForWrite($memberId, $projectId);
        $this->memberAdminService->adjustStampsToMember(
            $member,
            $stampCardId,
            $delta,
            $description ?? '管理画面での調整'
        );
    }
}
