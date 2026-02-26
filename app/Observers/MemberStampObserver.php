<?php

namespace App\Observers;

use App\Models\MemberStamp;
use App\Services\CouponIssueService;

class MemberStampObserver
{
    public function __construct(
        private CouponIssueService $couponIssueService
    ) {
    }

    public function saved(MemberStamp $memberStamp): void
    {
        if ($memberStamp->completed_at === null) {
            return;
        }
        if ($memberStamp->wasChanged('completed_at') === false) {
            return;
        }
        $member = $memberStamp->member;
        if ($member === null) {
            return;
        }
        $this->couponIssueService->issueForStampCompleted($member, (int) $memberStamp->stamp_card_id);
    }
}
