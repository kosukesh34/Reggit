<?php

namespace App\UseCases\Lottery;

use App\Models\User;
use App\Services\Lottery\LotteryPlayService;
use RuntimeException;

class MemberLotteryIndexUseCase
{
    public function __construct(
        private LotteryPlayService $lotteryPlayService,
    ) {}

    /**
     * @return array{
     *     gacha_enabled: bool,
     *     scratch_enabled: bool,
     *     entitlements: list<array{id: int, reward_type: string, expires_at: string|null}>,
     *     prize_labels: array{gacha: list<string>, scratch: list<string>}
     * }
     */
    public function handle(User $user): array
    {
        $member = $user->member;
        if ($member === null) {
            throw new RuntimeException('会員登録が必要です', 401);
        }

        return $this->lotteryPlayService->getMemberLotteryState($member);
    }
}
