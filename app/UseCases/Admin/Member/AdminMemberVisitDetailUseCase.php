<?php

namespace App\UseCases\Admin\Member;

use App\Models\Member;
use App\Models\MemberVisit;
use App\UseCases\Admin\Member\Data\AdminMemberVisitDetailOutput;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminMemberVisitDetailUseCase
{
    public function handle(int $projectId, int $memberId, int $visitId): AdminMemberVisitDetailOutput
    {
        $member = Member::query()
            ->with('user')
            ->where('project_id', $projectId)
            ->whereKey($memberId)
            ->first();
        if ($member === null) {
            throw new NotFoundHttpException('会員が見つかりません');
        }

        $visit = MemberVisit::query()
            ->with(['items.product', 'order'])
            ->where('project_id', $projectId)
            ->where('member_id', $memberId)
            ->whereKey($visitId)
            ->first();
        if ($visit === null) {
            throw new NotFoundHttpException('取引・来店履歴が見つかりません');
        }

        return new AdminMemberVisitDetailOutput($member, $visit);
    }
}
