<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Services\Admin\CouponAdminService;

class CouponController extends AdminBaseController
{
    public function __construct(
        private CouponAdminService $couponAdminService
    ) {
    }

    public function coupons()
    {
        $coupons = $this->couponAdminService->getPaginatedList(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function createCoupon()
    {
        $stampCards = $this->couponAdminService->getActiveStampCardsForSelect();
        return view('admin.coupons.create', compact('stampCards'));
    }

    public function storeCoupon(CouponStoreRequest $request)
    {
        $this->couponAdminService->create($this->resolveAdminProjectId(), $request->validated());
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを作成しました');
    }

    public function editCoupon(int $id)
    {
        $coupon = $this->couponAdminService->findOrFail($id);
        $stampCards = $this->couponAdminService->getActiveStampCardsForSelect();
        return view('admin.coupons.edit', compact('coupon', 'stampCards'));
    }

    public function updateCoupon(CouponUpdateRequest $request, int $id)
    {
        $this->couponAdminService->update($id, $request->validated());
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを更新しました');
    }

    public function deleteCoupon(int $id)
    {
        $this->couponAdminService->delete($id);
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを削除しました');
    }
}
