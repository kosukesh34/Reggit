<?php

namespace App\Infrastructure\Persistence\Eloquent\Coupon;

use App\Domain\Coupon\Aggregates\MemberCoupon as MemberCouponAggregate;
use App\Domain\Coupon\ValueObjects\MemberCouponId;
use App\Models\Coupon;
use App\Models\MemberCoupon as MemberCouponModel;

final class MemberCouponMapper
{
    public static function toDomain(MemberCouponModel $model): MemberCouponAggregate
    {
        $coupon = $model->coupon;
        $useDisplay = (string) ($coupon?->use_display ?? Coupon::USE_DISPLAY_BARCODE);
        $code = $coupon?->code;
        if ($useDisplay === Coupon::USE_DISPLAY_NONE && $code !== null && $code !== '') {
            $useDisplay = Coupon::USE_DISPLAY_BARCODE;
        }

        return new MemberCouponAggregate(
            new MemberCouponId((int) $model->id),
            (int) $model->project_id,
            (int) $model->member_id,
            (int) $model->coupon_id,
            (string) $model->status,
            $model->valid_from,
            $model->valid_until,
            $model->used_at,
            $model->coupon_name_snapshot,
            $coupon?->name,
            $code,
            $useDisplay,
        );
    }
}
