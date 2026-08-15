<?php

namespace App\Observers;

use App\Models\MemberLotteryEntitlement;
use App\Services\MemberLiffWalletNotifier;

class MemberLotteryEntitlementObserver
{
    public function __construct(
        private MemberLiffWalletNotifier $liffWalletNotifier,
    ) {}

    public function created(MemberLotteryEntitlement $entitlement): void
    {
        $this->notify($entitlement);
    }

    public function updated(MemberLotteryEntitlement $entitlement): void
    {
        $this->notify($entitlement);
    }

    public function deleted(MemberLotteryEntitlement $entitlement): void
    {
        $this->notify($entitlement);
    }

    private function notify(MemberLotteryEntitlement $entitlement): void
    {
        $member = $entitlement->member;
        if ($member === null) {
            return;
        }
        $this->liffWalletNotifier->notify($member);
    }
}
