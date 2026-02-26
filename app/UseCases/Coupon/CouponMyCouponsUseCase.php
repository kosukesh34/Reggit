<?php

namespace App\UseCases\Coupon;

use App\Models\ShopSetting;
use App\Services\UserService;
use App\UseCases\Coupon\Data\CouponListView;
use App\UseCases\Coupon\Data\MemberCoupon;
use Illuminate\Http\Request;

class CouponMyCouponsUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request): CouponListView
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new CouponListView([], null, true);
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
                    $themeColor
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
