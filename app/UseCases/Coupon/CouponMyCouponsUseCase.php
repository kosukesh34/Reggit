<?php

namespace App\UseCases\Coupon;

use App\Models\ShopSetting;
use App\UseCases\Coupon\Data\CouponListView;
use App\UseCases\Coupon\Data\MemberCoupon;
use App\UseCases\Member\Data\MemberUserInput;

class CouponMyCouponsUseCase
{
    public function handle(MemberUserInput $input): CouponListView
    {
        $user = $input->user;
        $member = $user->member;

        if ($member === null) {
            $projectId = $user->project_id ?? 1;
            $defaultImageUrl = ShopSetting::getCouponDefaultImageUrl($projectId);

            return new CouponListView([], $defaultImageUrl, true);
        }

        $coupons = $member->memberCoupons()
            ->with('coupon')
            ->orderBy('issued_at', 'desc')
            ->get()
            ->map(function ($memberCoupon) {
                $couponName = $memberCoupon->coupon_name_snapshot;
                if ($couponName === null || $couponName === '') {
                    $couponName = $memberCoupon->coupon?->name ?? '';
                }
                $displayStatus = $this->resolveDisplayStatus($memberCoupon);

                $designTemplate = (string) ($memberCoupon->coupon?->design_template ?? 'classic');
                $themeColor = $memberCoupon->coupon?->theme_color;
                $useDisplay = (string) ($memberCoupon->coupon?->use_display ?? 'none');
                $code = $memberCoupon->coupon?->code;

                return new MemberCoupon(
                    (int) $memberCoupon->id,
                    $couponName,
                    $memberCoupon->coupon?->image_url,
                    $displayStatus,
                    $this->toIsoString($memberCoupon->issued_at),
                    $this->toIsoString($memberCoupon->valid_from),
                    $this->toIsoString($memberCoupon->valid_until),
                    $this->toIsoString($memberCoupon->used_at),
                    $designTemplate,
                    $themeColor,
                    $memberCoupon->coupon?->description,
                    $useDisplay,
                    $code
                );
            })
            ->all();

        $defaultImageUrl = ShopSetting::getCouponDefaultImageUrl($member->project_id);

        return new CouponListView($coupons, $defaultImageUrl, false);
    }

    /** 表示用: available / used / expired */
    private function resolveDisplayStatus(\App\Models\MemberCoupon $memberCoupon): string
    {
        if ($memberCoupon->status === \App\Models\MemberCoupon::STATUS_USED) {
            return 'used';
        }
        $validUntil = $memberCoupon->valid_until;
        if ($validUntil !== null) {
            $end = $validUntil instanceof \DateTimeInterface ? $validUntil : new \DateTimeImmutable($validUntil);
            if ($end->getTimestamp() < time()) {
                return 'expired';
            }
        }

        return 'available';
    }

    private function toIsoString($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_string($value)) {
            return $value;
        }

        return null;
    }
}
