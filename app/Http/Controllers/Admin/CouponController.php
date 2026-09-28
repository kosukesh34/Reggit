<?php

namespace App\Http\Controllers\Admin;

use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\CouponRedemption;
use App\Models\MemberCoupon;
use App\UseCases\Admin\Coupon\AdminCouponEditFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponIndexUseCase;
use App\UseCases\Admin\Coupon\AdminCouponRedemptionsUseCase;

class CouponController extends AdminBaseController
{
    public function __construct(
        private AdminCouponIndexUseCase $indexUseCase,
        private AdminCouponEditFormUseCase $editFormUseCase,
        private AdminCouponRedemptionsUseCase $redemptionsUseCase,
    ) {}

    public function coupons()
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->indexUseCase->handle($projectId);

        return view('admin.coupons.index', [
            'coupons' => $output->coupons,
            'deliveryHistories' => $output->deliveryHistories,
        ]);
    }

    public function createCoupon()
    {
        $this->resolveAdminProjectId();

        return view('admin.coupons.create');
    }

    public function showCoupon(string $projectKey, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $coupon = Coupon::where('project_id', $projectId)->findOrFail($id);
        $deliveryQuery = CouponDelivery::where('project_id', $projectId)->where('coupon_id', $coupon->id);
        $memberCouponQuery = MemberCoupon::where('project_id', $projectId)->where('coupon_id', $coupon->id);

        return view('admin.coupons.show', [
            'coupon' => $coupon,
            'stats' => [
                'delivery_count' => (clone $deliveryQuery)->count(),
                'issued_count' => (clone $memberCouponQuery)->count(),
                'unused_count' => (clone $memberCouponQuery)->where('status', MemberCoupon::STATUS_UNUSED)->count(),
                'redeemed_count' => CouponRedemption::where('project_id', $projectId)->where('coupon_id', $coupon->id)->count(),
            ],
            'deliveries' => $deliveryQuery->orderByDesc('id')->paginate(10, ['*'], 'deliveries_page'),
        ]);
    }

    public function editCoupon(string $projectKey, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->editFormUseCase->handle($projectId, (int) $id);

        return view('admin.coupons.edit', [
            'coupon' => $output->coupon,
            'stampCards' => $output->stampCards,
        ]);
    }

    public function couponRedemptions(string $projectKey, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->redemptionsUseCase->handle($projectId, (int) $id);

        return view('admin.coupons.redemptions', ['view' => $output]);
    }
}
