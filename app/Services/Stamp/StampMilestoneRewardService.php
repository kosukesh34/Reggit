<?php

namespace App\Services\Stamp;

use App\Enums\StampRewardType;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\StampCard;
use App\Services\CouponIssueService;
use App\Services\Lottery\LotteryEntitlementService;

class StampMilestoneRewardService
{
    public function __construct(
        private CouponIssueService $couponIssueService,
        private LotteryEntitlementService $lotteryEntitlementService,
    ) {}

    public function grantReached(Member $member, StampCard $stampCard, int $previousBalance, int $newBalance): void
    {
        if ($newBalance <= $previousBalance) {
            return;
        }
        foreach ($this->reachedMilestones($stampCard, $previousBalance, $newBalance) as $milestone) {
            $this->grantOne($member, $stampCard, $milestone);
        }
    }

    /**
     * @return list<array{stamp_count: int, reward_type: string, coupon_id?: int}>
     */
    private function reachedMilestones(StampCard $stampCard, int $previousBalance, int $newBalance): array
    {
        $milestones = is_array($stampCard->reward_milestones) ? $stampCard->reward_milestones : [];
        $reached = [];
        foreach ($milestones as $milestone) {
            if (! is_array($milestone)) {
                continue;
            }
            $stampCount = (int) ($milestone['stamp_count'] ?? 0);
            if ($stampCount <= 0 || $previousBalance >= $stampCount || $stampCount > $newBalance) {
                continue;
            }
            $reached[] = $milestone;
        }

        return $reached;
    }

    /** @param array{stamp_count?: int, reward_type?: string, coupon_id?: int} $milestone */
    private function grantOne(Member $member, StampCard $stampCard, array $milestone): void
    {
        $rewardType = (string) ($milestone['reward_type'] ?? '');
        if ($rewardType === StampRewardType::COUPON) {
            $this->grantCoupon($member, (int) ($milestone['coupon_id'] ?? 0));

            return;
        }
        if ($rewardType === StampRewardType::GACHA) {
            $this->lotteryEntitlementService->grantForStamp(
                $member,
                (int) $stampCard->id,
                (int) ($milestone['stamp_count'] ?? 0),
            );
        }
    }

    private function grantCoupon(Member $member, int $couponId): void
    {
        if ($couponId <= 0) {
            return;
        }
        $coupon = Coupon::query()
            ->where('project_id', $member->project_id)
            ->whereKey($couponId)
            ->where('is_active', true)
            ->first();
        if ($coupon === null) {
            return;
        }
        $this->couponIssueService->issueIfEligible($member, $coupon);
    }
}
