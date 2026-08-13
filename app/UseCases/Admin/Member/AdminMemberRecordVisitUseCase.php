<?php

namespace App\UseCases\Admin\Member;

use App\Enums\MemberVisitSource;
use App\Services\Admin\MemberAdminService;
use App\Util\DateUtil;
use InvalidArgumentException;

class AdminMemberRecordVisitUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService
    ) {}

    public function handle(int $projectId, int $memberId, string $visitedAt, ?string $note = null): void
    {
        $member = $this->memberAdminService->findForWrite($memberId, $projectId);
        $visited = DateUtil::nullableCarbon($visitedAt);
        if ($visited === null) {
            throw new InvalidArgumentException('来店日時が不正です');
        }
        $this->memberAdminService->recordVisit(
            $member,
            $visited,
            MemberVisitSource::ADMIN,
            null,
            $note
        );
    }
}
