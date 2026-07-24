<?php

namespace App\UseCases\Lottery;

use App\Models\Member;
use App\Services\Lottery\LotteryEntitlementService;

class RevokeLotteryEntitlementUseCase
{
    public function __construct(
        private LotteryEntitlementService $lotteryEntitlementService,
    ) {}

    public function handle(Member $member, string $sourceType, string $sourceId): void
    {
        $this->lotteryEntitlementService->revokePendingForSource($member, $sourceType, $sourceId);
    }
}
