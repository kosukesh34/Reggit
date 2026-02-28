<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CouponBulkIssueRequest;
use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberAdminService;

class CouponController extends AdminBaseController
{
    public function __construct(
        private CouponAdminService $couponAdminService,
        private MemberAdminService $memberAdminService
    ) {
    }

    public function coupons()
    {
        $projectId = $this->resolveAdminProjectId();
        $coupons = $this->couponAdminService->getPaginatedList($projectId, 20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function createCoupon()
    {
        $projectId = $this->resolveAdminProjectId();
        $stampCards = $this->couponAdminService->getActiveStampCardsForSelect($projectId);
        return view('admin.coupons.create', compact('stampCards'));
    }

    public function storeCoupon(CouponStoreRequest $request)
    {
        $this->couponAdminService->create($this->resolveAdminProjectId(), $request->validated());
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを作成しました');
    }

    public function editCoupon(string|int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $coupon = $this->couponAdminService->findOrFail((int) $id, $projectId);
        $stampCards = $this->couponAdminService->getActiveStampCardsForSelect($projectId);
        return view('admin.coupons.edit', compact('coupon', 'stampCards'));
    }

    public function updateCoupon(CouponUpdateRequest $request, string|int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->couponAdminService->update((int) $id, $request->validated(), $projectId);
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを更新しました');
    }

    public function deleteCoupon(string|int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->couponAdminService->delete((int) $id, $projectId);
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを削除しました');
    }

    /** プレゼント配信（クーポン一括配布）画面 */
    public function bulkIssueForm()
    {
        $projectId = $this->resolveAdminProjectId();
        $coupons = $this->memberAdminService->getActiveCouponsForSelect($projectId);
        $memberCount = \App\Models\Member::where('project_id', $projectId)->count();
        return view('admin.coupons.bulk-issue', compact('coupons', 'memberCount'));
    }

    /** プレゼント配信（クーポン一括配布）実行 */
    public function bulkIssue(CouponBulkIssueRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        $memberIds = [];
        if (($validated['target'] ?? '') === 'selected') {
            $input = $request->input('member_ids_input', '');
            $memberIds = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $input))));
        }
        $issued = $this->memberAdminService->issueCouponToMembers($projectId, (int) $validated['coupon_id'], $memberIds);
        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', "{$issued}件の会員にクーポンを配布しました");
    }
}
