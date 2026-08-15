<?php

namespace App\Services;

use App\Events\MemberWalletUpdated;
use App\Models\Member;
use App\UseCases\Member\MemberWalletSummaryUseCase;
use App\Util\LogHelper;

class MemberLiffWalletNotifier
{
    public function __construct(
        private MemberWalletSummaryUseCase $walletSummaryUseCase,
    ) {}

    public function notify(Member $member): void
    {
        $user = $member->user;
        if ($user === null) {
            return;
        }

        try {
            $summary = $this->walletSummaryUseCase->handle($user);
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
