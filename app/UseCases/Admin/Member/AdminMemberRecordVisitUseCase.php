<?php

namespace App\UseCases\Admin\Member;

use App\Enums\MemberVisitSource;
use App\Services\Admin\MemberAdminService;
use Carbon\Carbon;

class AdminMemberRecordVisitUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService
    ) {}

    public function handle(int $projectId, int $memberId, string $visitedAt, ?string $note = null): void
    {
        $member = $this->memberAdminService->findForWrite($memberId, $projectId);
        $this->memberAdminService->recordVisit(
            $member,
            Carbon::parse($visitedAt),
            MemberVisitSource::ADMIN,
            null,
            $note
        );
    }
}
