<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;

class AdminMemberDeleteUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
    ) {}

    public function handle(int $projectId, int $memberId): void
    {
        $this->memberAdminService->delete($memberId, $projectId);
    }
}
