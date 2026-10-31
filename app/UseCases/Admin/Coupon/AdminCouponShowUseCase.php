<?php

namespace App\UseCases\Admin\Coupon;

use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\CouponRedemption;
use App\Models\MemberCoupon;

class AdminCouponShowUseCase
{
    /** クーポン詳細と配布・発行・利用の件数、配布履歴（10件ずつ） */
    public function handle(int $projectId, int $couponId): array
    {
        $coupon = Coupon::where('project_id', $projectId)->findOrFail($couponId);
        $deliveryQuery = CouponDelivery::where('project_id', $projectId)->where('coupon_id', $coupon->id);
        $memberCouponQuery = MemberCoupon::where('project_id', $projectId)->where('coupon_id', $coupon->id);

        return [
            'coupon' => $coupon,
            'stats' => [
                'delivery_count' => (clone $deliveryQuery)->count(),
                'issued_count' => (clone $memberCouponQuery)->count(),
                'unused_count' => (clone $memberCouponQuery)->where('status', MemberCoupon::STATUS_UNUSED)->count(),
                'redeemed_count' => CouponRedemption::where('project_id', $projectId)->where('coupon_id', $coupon->id)->count(),
            ],
            'deliveries' => $deliveryQuery->orderByDesc('id')->paginate(10, ['*'], 'deliveries_page'),
        ];
    }
}
