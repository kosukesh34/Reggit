<?php

namespace App\Observers;

use App\Models\PointTransaction;
use App\Services\MemberWalletSyncService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class PointTransactionObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private MemberWalletSyncService $memberWalletSyncService,
    ) {}

    public function created(PointTransaction $pointTransaction): void
    {
        $this->syncWallets($pointTransaction);
    }

    public function updated(PointTransaction $pointTransaction): void
    {
        $this->syncWallets($pointTransaction);
    }

    public function deleted(PointTransaction $pointTransaction): void
    {
        $this->syncWallets($pointTransaction);
    }

    private function syncWallets(PointTransaction $pointTransaction): void
    {
        $member = $pointTransaction->member()->first();
        if ($member === null) {
            return;
        }

        $this->memberWalletSyncService->syncAfterMemberChanged(
            $member,
            'point_transaction:'.$pointTransaction->id
        );
    }
}
