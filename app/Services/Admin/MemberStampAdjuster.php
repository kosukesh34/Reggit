<?php

namespace App\Services\Admin;

use App\Services\MemberRankSyncService;

/** 管理者による会員スタンプの加算・減算。調整後にランクを同期する。 */
class MemberStampAdjuster
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private MemberBenefitService $memberBenefitService,
        private MemberRankSyncService $memberRankSyncService,
    ) {}

    public function adjust(int $projectId, int $memberId, int $stampCardId, int $add, int $subtract, ?string $description = null): void
    {
        $delta = $add - $subtract;
        if ($delta === 0) {
            throw new \InvalidArgumentException('スタンプの加算または減算を指定してください');
        }

        $member = $this->memberAdminService->findForWrite($memberId, $projectId);
        $this->memberBenefitService->adjustStampsToMember(
            $member,
            $stampCardId,
            $delta,
            $description ?? '管理画面での調整'
        );
        $this->memberRankSyncService->sync($member);
    }
}
