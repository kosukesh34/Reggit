<?php

namespace App\Observers;

use App\Models\MemberStamp;
use App\Services\CouponIssueService;
use App\Services\MemberLiffWalletNotifier;

class MemberStampObserver
{
    public function __construct(
        private CouponIssueService $couponIssueService,
        private MemberLiffWalletNotifier $liffWalletNotifier,
    ) {}

    public function saved(MemberStamp $memberStamp): void
    {
        $this->notifyWalletIfChanged($memberStamp);
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
        $this->couponIssueService->issueForStampCompleted(
            $member,
            (int) $memberStamp->stamp_card_id,
            $memberStamp->issueSourceType,
            $memberStamp->issueSourceId,
            (int) $memberStamp->cycle_count,
        );
    }

    private function notifyWalletIfChanged(MemberStamp $memberStamp): void
    {
        if ($memberStamp->wasRecentlyCreated === false
            && $memberStamp->wasChanged('current_stamps') === false
            && $memberStamp->wasChanged('completed_at') === false) {
            return;
        }
        $member = $memberStamp->member;
        if ($member === null) {
            return;
        }
        $this->liffWalletNotifier->notify($member);
    }
}
