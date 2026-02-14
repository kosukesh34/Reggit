<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;

class CouponIssueService
{
    public function issueForFirstRegistration(Member $member): void
    {
        $coupons = Coupon::where('project_id', $member->project_id)
            ->where('is_active', true)
            ->where('issue_trigger', Coupon::ISSUE_TRIGGER_FIRST_REGISTRATION)
            ->get();

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
}
