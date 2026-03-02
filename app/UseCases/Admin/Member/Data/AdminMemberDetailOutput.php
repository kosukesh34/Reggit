<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\Member;
use Illuminate\Support\Collection;

/**
 * 管理画面会員詳細 UseCase の出力。不変。
 *
 * @param  Collection<int, \App\Models\Coupon>  $activeCoupons
 * @param  Collection<int, \App\Models\StampCard>  $activeStampCards
 */
class AdminMemberDetailOutput
{
    public function __construct(
        public Member $member,
        public Collection $activeCoupons,
        public Collection $activeStampCards
    ) {}
}
