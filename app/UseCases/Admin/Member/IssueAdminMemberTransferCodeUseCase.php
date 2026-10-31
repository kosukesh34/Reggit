<?php

namespace App\UseCases\Admin\Member;

use App\Models\Member;
use App\Models\Scopes\ProjectScope;
use App\Services\Member\Data\IssuedTransferCode;
use App\Services\Member\MemberTransferCodeIssuer;
use InvalidArgumentException;

class IssueAdminMemberTransferCodeUseCase
{
    public function __construct(
        private MemberTransferCodeIssuer $transferCodes,
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

        return $this->transferCodes->issue($member->user, $adminId);
    }
}
