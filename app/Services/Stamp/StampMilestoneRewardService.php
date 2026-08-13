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

    public function grantReached(
        Member $member,
        StampCard $stampCard,
        int $previousBalance,
        int $newBalance,
        int $cycleIndex = 0,
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
    ): void {
        if ($newBalance <= $previousBalance) {
            return;
        }
        foreach ($this->reachedMilestones($stampCard, $previousBalance, $newBalance) as $milestone) {
            $this->grantOne($member, $stampCard, $milestone, $cycleIndex, $issueSourceType, $issueSourceId);
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
    private function grantOne(
        Member $member,
        StampCard $stampCard,
        array $milestone,
        int $cycleIndex,
        ?string $issueSourceType,
        ?string $issueSourceId,
    ): void {
        $rewardType = (string) ($milestone['reward_type'] ?? '');
        if ($rewardType === StampRewardType::COUPON) {
            $this->grantCoupon(
                $member,
                $stampCard,
                (int) ($milestone['coupon_id'] ?? 0),
                (int) ($milestone['stamp_count'] ?? 0),
                $cycleIndex,
                $issueSourceType,
                $issueSourceId,
            );

            return;
        }
        if ($rewardType === StampRewardType::GACHA) {
            $this->lotteryEntitlementService->grantForStamp(
                $member,
                (int) $stampCard->id,
                (int) ($milestone['stamp_count'] ?? 0),
                $cycleIndex,
            );
        }
    }

    private function grantCoupon(
        Member $member,
        StampCard $stampCard,
        int $couponId,
        int $stampCount,
        int $cycleIndex,
        ?string $issueSourceType,
        ?string $issueSourceId,
    ): void {
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
        $this->couponIssueService->issueForStampGrant(
            $member,
            $coupon,
            $issueSourceType,
            $issueSourceId,
            (int) $stampCard->id,
            $cycleIndex,
            $stampCount,
        );
    }
}
