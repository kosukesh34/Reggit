<?php

namespace App\UseCases\Lottery;

use App\Models\User;
use App\Services\Lottery\LotteryPlayService;
use RuntimeException;

class PlayLotteryEntitlementUseCase
{
    public function __construct(
        private LotteryPlayService $lotteryPlayService,
    ) {}

    /**
     * @return array{
     *     entitlement_id: int,
     *     reward_type: string,
     *     is_win: bool,
     *     coupon: array{id: int, name: string, description: string|null, image_url: string|null}|null,
     *     member_coupon_id: int|null
     * }
     */
    public function handle(User $user, int $entitlementId): array
    {
        $member = $user->member;
        if ($member === null) {
            throw new RuntimeException('会員登録が必要です', 401);
        }

        return $this->lotteryPlayService->play($member, $entitlementId);
    }
}
