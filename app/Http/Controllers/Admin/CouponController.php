<?php

namespace App\Http\Controllers\Admin;

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
