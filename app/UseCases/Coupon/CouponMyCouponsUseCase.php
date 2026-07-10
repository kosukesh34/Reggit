<?php

namespace App\UseCases\Coupon;

use App\Domain\Coupon\Policies\CouponDisplayStatusPolicy;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Coupon;
use App\Models\User;
use App\UseCases\Coupon\Data\CouponListView;
use App\UseCases\Coupon\Data\MemberCoupon;

class CouponMyCouponsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
        private CouponDisplayStatusPolicy $couponDisplayStatusPolicy,
    ) {}

    public function handle(User $user): CouponListView
    {
        $member = $user->member;

        if ($member === null) {
            $projectId = $user->project_id ?? 0;
            $defaultImageUrl = $this->shopSettingRepository->getCouponDefaultImageUrl($projectId);

            return new CouponListView([], $defaultImageUrl, true);
        }

        $now = now();
        $coupons = $member->memberCoupons()
            ->with('coupon')
            ->orderBy('issued_at', 'desc')
            ->get()
            ->map(function ($memberCoupon) use ($now) {
                $couponName = $memberCoupon->coupon_name_snapshot;
                if ($couponName === null || $couponName === '') {
                    $couponName = $memberCoupon->coupon?->name ?? '';
                }
                $displayStatus = $this->couponDisplayStatusPolicy->resolve(
                    (string) $memberCoupon->status,
                    $memberCoupon->valid_from,
                    $memberCoupon->valid_until,
                    $now,
                );

                $designTemplate = (string) ($memberCoupon->coupon?->design_template ?? 'classic');
                $themeColor = $memberCoupon->coupon?->theme_color;
                $code = $memberCoupon->coupon?->code;
                $useDisplay = (string) ($memberCoupon->coupon?->use_display ?? Coupon::USE_DISPLAY_BARCODE);
                if ($useDisplay === Coupon::USE_DISPLAY_NONE && $code !== null && $code !== '') {
                    $useDisplay = Coupon::USE_DISPLAY_BARCODE;
                }

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

        $defaultImageUrl = $this->shopSettingRepository->getCouponDefaultImageUrl($member->project_id);

        return new CouponListView($coupons, $defaultImageUrl, false);
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
