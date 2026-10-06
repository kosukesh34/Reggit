<?php

namespace App\Services;

use App\Events\MemberWalletUpdated;
use App\Models\Member;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;

class MemberLiffWalletNotifier
{
    public function __construct(
        private MemberWalletSummaryService $walletSummaryService,
    ) {}

    public function notify(Member $member): void
    {
        DB::afterCommit(function () use ($member): void {
            $freshMember = $member->fresh();
            if ($freshMember !== null) {
                $this->broadcast($freshMember);
            }
        });
    }

    private function broadcast(Member $member): void
    {
        $user = $member->user;
        if ($user === null) {
            return;
        }

        try {
            $summary = $this->walletSummaryService->summarize($user);
            event(new MemberWalletUpdated(
                (int) $member->project_id,
                (int) $member->id,
                (int) $summary['points'],
                (int) $summary['stamps_total'],
                (int) $summary['pending_lottery_count'],
            ));
        } catch (\Throwable $e) {
            LogHelper::warning('[LiffWallet] broadcast failed', [
                'member_id' => (int) $member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
