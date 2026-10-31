<?php

namespace App\Services\Smaregi;

use App\Domain\Smaregi\Services\SmaregiInboundAwardDescription;
use App\Enums\LotterySourceType;
use App\Enums\MemberCouponSource;
use App\Models\Member;
use App\Services\CouponIssueService;
use App\Services\Lottery\LotteryEntitlementService;

/**
 * スマレジ取引に紐づくポイント／スタンプの付与・取消を行う。
 */
class SmaregiInboundAwardApplier
{
    public function __construct(
        private SmaregiInboundPointAwarder $points,
        private SmaregiInboundStampAwarder $stamps,
        private LotteryEntitlementService $lotteryEntitlementService,
        private CouponIssueService $couponIssueService,
    ) {}

    public function awardForPurchase(
        Member $member,
        float $amount,
        string $headId,
        bool $hasSmaregiManagedPointDelta,
        ?int $orderId = null,
    ): void {
        $this->stamps->awardStamps($member, $amount, $headId, $orderId);
        $this->points->awardPoints($member, $amount, $headId, $hasSmaregiManagedPointDelta, $orderId);
        if ($amount <= 0) {
            return;
        }
        $this->lotteryEntitlementService->grantForPurchase(
            $member,
            $amount,
            LotterySourceType::SMAREGI_TRANSACTION,
            $headId,
        );
    }

    public function reverseForCancel(Member $member, string $headId): void
    {
        $this->points->reverseAwardedPoints($member, $headId, SmaregiInboundAwardDescription::REASON_CANCEL);
        $this->stamps->reverseAwardedStamps($member, $headId, SmaregiInboundAwardDescription::REASON_CANCEL);
        $this->revokeIssuedRewards($member, $headId);
    }

    public function reverseForReturn(
        Member $member,
        float $absAmount,
        string $headId,
        bool $hasSmaregiManagedPointDelta,
    ): void {
        if (! $hasSmaregiManagedPointDelta) {
            $this->points->reversePointsByAmount(
                $member,
                $absAmount,
                $headId,
                SmaregiInboundAwardDescription::REASON_RETURN,
            );
        }
        $this->stamps->reverseStampsByAmount(
            $member,
            $absAmount,
            $headId,
            SmaregiInboundAwardDescription::REASON_RETURN,
        );
        $this->revokeIssuedRewards($member, $headId);
    }

    private function revokeIssuedRewards(Member $member, string $headId): void
    {
        $this->lotteryEntitlementService->revokePendingForSource(
            $member,
            LotterySourceType::SMAREGI_TRANSACTION,
            $headId,
        );
        $this->couponIssueService->revokeUnusedForSource(
            $member,
            MemberCouponSource::SMAREGI_TRANSACTION,
            $headId,
        );
    }
}
