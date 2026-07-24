<?php

namespace App\UseCases\Lottery;

use App\Models\Member;
use App\Services\Lottery\LotteryEntitlementService;

class GrantLotteryEntitlementUseCase
{
    public function __construct(
        private LotteryEntitlementService $lotteryEntitlementService,
    ) {}

    public function handle(
        Member $member,
        float $amount,
        string $sourceType,
        string $sourceId,
    ): void {
        $this->lotteryEntitlementService->grantForPurchase($member, $amount, $sourceType, $sourceId);
    }
}
