<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use Carbon\Carbon;

class CouponIssueService
{
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
            $alreadyThisYear = MemberCoupon::where('member_id', $member->id)
                ->where('coupon_id', $coupon->id)
                ->whereYear('issued_at', $now->year)
                ->exists();
            if ($alreadyThisYear) {
                continue;
            }
            if ($coupon->start_at !== null && $coupon->start_at->gt($now)) {
                continue;
            }
            if ($coupon->end_at !== null && $coupon->end_at->lt($now)) {
                continue;
            }
            $this->createMemberCoupon($member, $coupon, $now);
        }
    }

    /** 管理画面またはバッチから手動でクーポンを1件発行する */
    public function issueManualCoupon(Member $member, Coupon $coupon): void
    {
        $now = now();
        $this->createMemberCoupon($member, $coupon, $now);
    }

    private function issueByTrigger(Member $member, string $trigger): void
    {
        $coupons = Coupon::where('project_id', $member->project_id)
            ->where('is_active', true)
            ->where('issue_trigger', $trigger)
            ->get();
        $this->issueCouponsToMember($member, $coupons);
    }

    /** @param \Illuminate\Support\Collection<int, Coupon> $coupons */
    private function issueCouponsToMember(Member $member, $coupons): void
    {
        $now = now();
        foreach ($coupons as $coupon) {
            $alreadyIssued = MemberCoupon::where('member_id', $member->id)
                ->where('coupon_id', $coupon->id)
                ->exists();
            if ($alreadyIssued) {
                continue;
            }
            if ($coupon->start_at !== null && $coupon->start_at->gt($now)) {
                continue;
            }
            if ($coupon->end_at !== null && $coupon->end_at->lt($now)) {
                continue;
            }
            $this->createMemberCoupon($member, $coupon, $now);
        }
    }

    private function createMemberCoupon(Member $member, Coupon $coupon, Carbon $now): void
    {
        MemberCoupon::create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'coupon_name_snapshot' => $coupon->name,
            'status' => MemberCoupon::STATUS_UNUSED,
            'issued_at' => $now,
            'valid_from' => $coupon->start_at ?? $now,
            'valid_until' => $coupon->end_at,
        ]);
    }
}
