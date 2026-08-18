<?php

namespace App\UseCases\Coupon;

use App\Models\Member;
use App\Services\IssueCouponOnRankUpService;

class IssueCouponOnRankUpUseCase
{
    public function __construct(
        private IssueCouponOnRankUpService $issueCouponOnRankUpService,
    ) {}

    public function handle(Member $member, string $previousRank, string $newRank): void
    {
        $this->issueCouponOnRankUpService->handle($member, $previousRank, $newRank);
    }
}
