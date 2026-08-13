<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;

class AdminMemberAdjustStampsUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService
    ) {}

    public function handle(int $projectId, int $memberId, int $stampCardId, int $add, int $subtract, ?string $description = null): void
    {
        $delta = $add - $subtract;
        if ($delta === 0) {
            throw new \InvalidArgumentException('スタンプの加算または減算を指定してください');
        }

        $member = $this->memberAdminService->findForWrite($memberId, $projectId);
        $this->memberAdminService->adjustStampsToMember(
            $member,
            $stampCardId,
            $delta,
            $description ?? '管理画面での調整'
        );
    }
}
