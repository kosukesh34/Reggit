<?php

namespace App\UseCases\Coupon;

use App\Models\ShopSetting;
use App\Services\UserService;
use App\UseCases\Coupon\Dto\CouponListDto;
use App\UseCases\Coupon\Dto\MemberCouponDto;
use Illuminate\Http\Request;

class CouponMyCouponsUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request): CouponListDto
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new CouponListDto([], null, true);
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

                return new MemberCouponDto(
                    (int) $memberCoupon->id,
                    $couponName,
                    $memberCoupon->coupon?->image_url,
                    (string) $memberCoupon->status,
                    $this->toIsoString($memberCoupon->issued_at),
                    $this->toIsoString($memberCoupon->valid_from),
                    $this->toIsoString($memberCoupon->valid_until),
                    $this->toIsoString($memberCoupon->used_at)
                );
            })
            ->all();

        $defaultImageUrl = ShopSetting::getCouponDefaultImageUrl($member->project_id);

        return new CouponListDto($coupons, $defaultImageUrl, false);
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
