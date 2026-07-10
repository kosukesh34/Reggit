<?php

namespace App\Services;

use App\Domain\Coupon\Policies\CouponIssuePolicy;
use App\Domain\Coupon\Repositories\MemberCouponRepository;
use App\Domain\Coupon\ValueObjects\PendingIssuedCoupon;
use App\Models\Coupon;
use App\Models\Member;
use Illuminate\Support\Collection;

class CouponIssueService
{
    public function __construct(
        private MemberCouponRepository $memberCouponRepository,
        private CouponIssuePolicy $couponIssuePolicy,
    ) {}

    public function issueForFirstRegistration(Member $member): void
    {
        $this->issueByTrigger($member, Coupon::ISSUE_TRIGGER_FIRST_REGISTRATION);
    }

    /** 初回注文完了時に発行（注文が completed になった時点で、その会員の完了注文が1件のとき） */
    public function issueForFirstOrder(Member $member): void
    {
        $this->issueByTrigger($member, Coupon::ISSUE_TRIGGER_FIRST_ORDER);
    }

    /** 指定スタンプカード達成時に発行 */
    public function issueForStampCompleted(Member $member, int $stampCardId): void
    {
        $coupons = Coupon::where('project_id', $member->project_id)
            ->where('is_active', true)
            ->where('issue_trigger', Coupon::ISSUE_TRIGGER_STAMP_COMPLETED)
            ->where('stamp_card_id', $stampCardId)
            ->get();
        $this->issueCouponsToMember($member, $coupons);
    }

    /** 誕生月に1回のみ発行（同日・同月に既に発行済みの同一クーポンはスキップ） */
    public function issueForBirthday(Member $member): void
    {
        if ($member->birthday === null) {
            return;
        }
        $now = now();
        $birthMonth = (int) $member->birthday->format('n');
        if ($birthMonth !== (int) $now->format('n')) {
            return;
        }
        $coupons = Coupon::where('project_id', $member->project_id)
            ->where('is_active', true)
            ->where('issue_trigger', Coupon::ISSUE_TRIGGER_BIRTHDAY)
            ->get();
        foreach ($coupons as $coupon) {
            $alreadyThisYear = $this->memberCouponRepository->hasIssuedInYear(
                (int) $member->id,
                (int) $coupon->id,
                (int) $now->year,
                (int) $member->project_id,
            );
            if ($this->couponIssuePolicy->canIssueBirthdayThisYear(
                $alreadyThisYear,
                $coupon->start_at,
                $coupon->end_at,
                $now,
            ) === false) {
                continue;
            }
            $this->createMemberCoupon($member, $coupon, $now);
        }
    }

    /** 管理画面またはバッチから手動でクーポンを1件発行する */
    public function issueManualCoupon(Member $member, Coupon $coupon): void
    {
        $this->createMemberCoupon($member, $coupon, now());
    }

    private function issueByTrigger(Member $member, string $trigger): void
    {
        $coupons = Coupon::where('project_id', $member->project_id)
            ->where('is_active', true)
            ->where('issue_trigger', $trigger)
            ->get();
        $this->issueCouponsToMember($member, $coupons);
    }

    /** @param Collection<int, Coupon> $coupons */
    private function issueCouponsToMember(Member $member, $coupons): void
    {
        $now = now();
        foreach ($coupons as $coupon) {
            $alreadyIssued = $this->memberCouponRepository->hasIssued(
                (int) $member->id,
                (int) $coupon->id,
                (int) $member->project_id,
            );
            if ($this->couponIssuePolicy->canIssueOnce(
                $alreadyIssued,
                $coupon->start_at,
                $coupon->end_at,
                $now,
            ) === false) {
                continue;
            }
            $this->createMemberCoupon($member, $coupon, $now);
        }
    }

    private function createMemberCoupon(Member $member, Coupon $coupon, \DateTimeInterface $now): void
    {
        [$validFrom, $validUntil] = $this->couponIssuePolicy->resolveValidityWindow(
            $coupon->start_at,
            $coupon->end_at,
            $now,
        );

        $this->memberCouponRepository->issue(new PendingIssuedCoupon(
            (int) $member->project_id,
            (int) $member->id,
            (int) $coupon->id,
            (string) $coupon->name,
            $now,
            $validFrom,
            $validUntil,
        ));
    }
}
