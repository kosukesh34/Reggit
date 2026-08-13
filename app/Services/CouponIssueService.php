<?php

namespace App\Services;

use App\Domain\Coupon\Policies\CouponIssuePolicy;
use App\Domain\Coupon\Repositories\MemberCouponRepository;
use App\Domain\Coupon\ValueObjects\PendingIssuedCoupon;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Util\LogHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
    public function issueForStampCompleted(
        Member $member,
        int $stampCardId,
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
        int $cycleIndex = 0,
    ): void {
        $coupons = Coupon::where('project_id', $member->project_id)
            ->where('is_active', true)
            ->where('issue_trigger', Coupon::ISSUE_TRIGGER_STAMP_COMPLETED)
            ->where('stamp_card_id', $stampCardId)
            ->get();
        foreach ($coupons as $coupon) {
            $this->issueForStampGrant(
                $member,
                $coupon,
                $issueSourceType,
                $issueSourceId,
                $stampCardId,
                $cycleIndex,
                0,
            );
        }
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

    public function issueIfEligible(Member $member, Coupon $coupon): void
    {
        $this->issueCouponsToMember($member, collect([$coupon]));
    }

    public function issueForStampGrant(
        Member $member,
        Coupon $coupon,
        ?string $issueSourceType,
        ?string $issueSourceId,
        int $stampCardId,
        int $cycleIndex,
        int $stampCount,
    ): void {
        $now = now();
        if ($this->couponIssuePolicy->isWithinIssuePeriod($coupon->start_at, $coupon->end_at, $now) === false) {
            return;
        }
        $grantKey = $stampCardId.':'.$cycleIndex.':'.$stampCount.':'.(int) $coupon->id;
        if ($this->memberCouponRepository->hasIssuedGrantKey(
            (int) $member->id,
            $grantKey,
            (int) $member->project_id,
        )) {
            return;
        }
        $this->createMemberCoupon($member, $coupon, $now, $issueSourceType, $issueSourceId, $grantKey);
    }

    public function revokeUnusedForSource(Member $member, string $sourceType, string $sourceId): void
    {
        if ($sourceType === '' || $sourceId === '') {
            return;
        }
        $deleted = (int) DB::transaction(fn (): int => $this->memberCouponRepository->deleteUnusedForSource(
            (int) $member->project_id,
            (int) $member->id,
            $sourceType,
            $sourceId,
        ));
        if ($deleted <= 0) {
            return;
        }
        LogHelper::info('[Coupon] revoked unused issued coupons on cancel', [
            'project_id' => (int) $member->project_id,
            'member_id' => (int) $member->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'deleted' => $deleted,
        ]);
    }

    /**
     * 管理画面またはバッチから手動でクーポンを1件発行する
     *
     * @param  bool  $shouldNotify  false のとき MemberCouponObserver の即時通知を抑止する（ガチャ演出後に別途通知する用）
     */
    public function issueManualCoupon(Member $member, Coupon $coupon, bool $shouldNotify = true): void
    {
        if ($shouldNotify) {
            $this->createMemberCoupon($member, $coupon, now());

            return;
        }

        MemberCoupon::withoutEvents(function () use ($member, $coupon): void {
            $this->createMemberCoupon($member, $coupon, now());
        });
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

    private function createMemberCoupon(
        Member $member,
        Coupon $coupon,
        \DateTimeInterface $now,
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
        ?string $issueGrantKey = null,
    ): void {
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
            $issueSourceType,
            $issueSourceId,
            $issueGrantKey,
        ));
    }
}
