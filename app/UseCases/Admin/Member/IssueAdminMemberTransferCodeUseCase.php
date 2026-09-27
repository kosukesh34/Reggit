<?php

namespace App\UseCases\Admin\Member;

use App\Models\Member;
use App\Models\Scopes\ProjectScope;
use App\UseCases\Member\Data\IssuedTransferCode;
use App\UseCases\Member\IssueMemberTransferCodeUseCase;
use InvalidArgumentException;

class IssueAdminMemberTransferCodeUseCase
{
    public function __construct(
        private IssueMemberTransferCodeUseCase $issueMemberTransferCodeUseCase,
    ) {}

    public function handle(int $projectId, int $memberId, ?int $adminId): IssuedTransferCode
    {
        $member = Member::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->with('user')
            ->where('project_id', $projectId)
            ->whereKey($memberId)
            ->first();

        if ($member === null || $member->user === null) {
            throw new InvalidArgumentException('会員が見つかりません');
        }

        return $this->issueMemberTransferCodeUseCase->handle($member->user, $adminId);
    }
}
