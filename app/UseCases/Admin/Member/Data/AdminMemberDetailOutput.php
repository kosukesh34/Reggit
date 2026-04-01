<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\StampCard;
use Illuminate\Support\Collection;

/**
 * 管理画面会員詳細 UseCase の出力。不変。
 *
 * @param  Collection<int, Coupon>  $activeCoupons
 * @param  Collection<int, StampCard>  $activeStampCards
 * @param  array<string, bool|int|float|string|array|object|null>  $purchaseSummary
 */
class AdminMemberDetailOutput
{
    public function __construct(
        public Member $member,
        public Collection $activeCoupons,
        public Collection $activeStampCards,
        public array $purchaseSummary = []
    ) {}
}
