<?php

namespace App\UseCases\Lottery;

use App\Models\User;
use App\Services\Lottery\LotteryPlayService;
use App\Services\Member\CurrentMemberPrincipal;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NotifyLotteryWinUseCase
{
    public function __construct(
        private LotteryPlayService $lotteryPlayService,
        private CurrentMemberPrincipal $principals,
    ) {}

    public function handle(User $user, int $entitlementId): void
    {
        DB::transaction(function () use ($user, $entitlementId): void {
            $member = $this->principals->lock($user, (int) $user->project_id)?->member;
            if ($member === null) {
                throw new RuntimeException('会員登録が必要です', 401);
            }

            $this->lotteryPlayService->notifyWin($member, $entitlementId);
        });
    }
}
