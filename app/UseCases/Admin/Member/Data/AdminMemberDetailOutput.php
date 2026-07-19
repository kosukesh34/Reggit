<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 管理画面会員詳細 UseCase の出力。不変。
 *
 * @param  array<string, mixed>  $purchaseSummary
 * @param  array{
 *     visits_q: string,
 *     points_q: string,
 *     coupons_q: string,
 *     stamps_q: string,
 *     rank_q: string,
 *     redemptions_q: string
 * }  $historyFilters
 */
class AdminMemberDetailOutput
{
    public function __construct(
        public Member $member,
        public array $purchaseSummary = [],
        public ?LengthAwarePaginator $visits = null,
        public ?LengthAwarePaginator $pointTransactions = null,
        public ?LengthAwarePaginator $memberCoupons = null,
        public ?LengthAwarePaginator $stampTransactions = null,
        public ?LengthAwarePaginator $rankHistories = null,
        public ?LengthAwarePaginator $couponRedemptions = null,
        public array $historyFilters = [],
    ) {}
}
