<?php

namespace App\UseCases\Coupon;

use App\Domain\Coupon\Policies\CouponDisplayStatusPolicy;
use App\Domain\Settings\Repositories\CouponNotificationSettings;
use App\Domain\Shared\Calendar\HolidayCalendar;
use App\Enums\CouponApplyConditionDivision;
use App\Enums\CouponCombineDivision;
use App\Models\Coupon;
use App\Models\User;
use App\UseCases\Coupon\Data\CouponListView;
use App\UseCases\Coupon\Data\MemberCoupon;
use App\Util\AdminContext;

class CouponMyCouponsUseCase
{
    public function __construct(
        private CouponNotificationSettings $shopSettingRepository,
        private CouponDisplayStatusPolicy $couponDisplayStatusPolicy,
        private HolidayCalendar $holidays,
    ) {}

    public function handle(User $user): CouponListView
    {
        $member = $user->member;

        if ($member === null) {
            $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
            $defaultImageUrl = $this->shopSettingRepository->getCouponDefaultImageUrl($projectId);

            return new CouponListView([], $defaultImageUrl, true);
        }

        $now = now();
        $isHoliday = $this->holidays->isHoliday($now);
        $coupons = $member->memberCoupons()
            ->with('coupon.targetProducts')
            ->orderBy('issued_at', 'desc')
            ->get()
            ->map(function ($memberCoupon) use ($now, $isHoliday) {
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
                $usageWindow = $memberCoupon->coupon?->usageWindow();
                if ($displayStatus === CouponDisplayStatusPolicy::AVAILABLE && $usageWindow !== null && ! $usageWindow->allows($now, $isHoliday)) {
                    $displayStatus = CouponDisplayStatusPolicy::OUTSIDE_USAGE_TIME;
                }

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
                    // コードは全会員共通のため、使えないクーポンでは返さない
                    $displayStatus === CouponDisplayStatusPolicy::AVAILABLE ? $code : null,
                    (string) ($memberCoupon->coupon?->image_fit ?? Coupon::IMAGE_FIT_COVER),
                    $this->positiveAmount($memberCoupon->coupon?->min_order_amount),
                    $this->positiveAmount($memberCoupon->coupon?->max_order_amount),
                    (string) ($memberCoupon->coupon?->combine_division ?? CouponCombineDivision::ALLOWED),
                    $memberCoupon->coupon?->apply_condition_division === CouponApplyConditionDivision::INCLUDE_TARGET_PRODUCT
                        ? $memberCoupon->coupon->targetProducts->pluck('name')->all()
                        : [],
                    $usageWindow?->label(),
                );
            })
            ->all();

        $defaultImageUrl = $this->shopSettingRepository->getCouponDefaultImageUrl($member->project_id);

        return new CouponListView($coupons, $defaultImageUrl, false);
    }

    /** 0円・未設定は「条件なし」 */
    private function positiveAmount(mixed $value): ?int
    {
        return $value !== null && (float) $value > 0 ? (int) $value : null;
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
