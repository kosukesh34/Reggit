<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\StampCard;
use Illuminate\Support\Collection;

/**
 * 管理画面会員編集フォーム UseCase の出力。不変。
 *
 * @param  array<string, string>  $rankOptions  key => label
 * @param  Collection<int, Coupon>  $activeCoupons
 * @param  Collection<int, StampCard>  $activeStampCards
 * @param  list<array<string, mixed>>  $customProfileFields
 */
class AdminMemberEditOutput
{
    public function __construct(
        public Member $member,
        public array $rankOptions,
        public Collection $activeCoupons,
        public Collection $activeStampCards,
        public array $customProfileFields = [],
        public array $customerStoreOptions = [],
    ) {}
}
