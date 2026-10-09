<?php

namespace App\Observers;

use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Scopes\ProjectScope;
use App\Services\MemberWalletSyncService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class PointTransactionObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private MemberWalletSyncService $memberWalletSyncService,
    ) {}

    public function created(PointTransaction $pointTransaction): void
    {
        $this->recalcLifetimeGrantedForMember($pointTransaction);
        $this->syncWallets($pointTransaction);
    }

    public function updated(PointTransaction $pointTransaction): void
    {
        $this->recalcLifetimeGrantedForMember($pointTransaction);
        $this->syncWallets($pointTransaction);
    }

    public function deleted(PointTransaction $pointTransaction): void
    {
        $this->recalcLifetimeGrantedForMember($pointTransaction);
        $this->syncWallets($pointTransaction);
    }

    private function recalcLifetimeGrantedForMember(PointTransaction $pointTransaction): void
    {
        $member = Member::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $pointTransaction->project_id)
            ->whereKey($pointTransaction->member_id)
            ->first();
        if ($member === null) {
            return;
        }

        $member->recalcLifetimeGrantedPoints();
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
