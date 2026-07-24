<?php

namespace App\UseCases\Lottery;

use App\Models\User;
use App\Services\Lottery\LotteryPlayService;
use RuntimeException;

class NotifyLotteryWinUseCase
{
    public function __construct(
        private LotteryPlayService $lotteryPlayService,
    ) {}

    public function handle(User $user, int $entitlementId): void
    {
        $member = $user->member;
        if ($member === null) {
            throw new RuntimeException('会員登録が必要です', 401);
        }

        $this->lotteryPlayService->notifyWin($member, $entitlementId);
    }
}
