<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CouponBulkIssueRequest;
use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Services\Admin\CouponAdminService;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use App\UseCases\Admin\Coupon\AdminCouponCreateFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeleteUseCase;
use App\UseCases\Admin\Coupon\AdminCouponEditFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponIndexUseCase;
use App\UseCases\Admin\Coupon\AdminCouponStoreUseCase;
use App\UseCases\Admin\Coupon\AdminCouponUpdateUseCase;

class CouponController extends AdminBaseController
{
    private AdminCouponIndexUseCase $indexUseCase;

    private AdminCouponCreateFormUseCase $createFormUseCase;

    private AdminCouponStoreUseCase $storeUseCase;

    private CouponAdminService $couponAdminService;

    private AdminCouponEditFormUseCase $editFormUseCase;

    private AdminCouponUpdateUseCase $updateUseCase;

    private AdminCouponDeleteUseCase $deleteUseCase;

    private AdminCouponBulkIssueFormUseCase $bulkIssueFormUseCase;

    private AdminCouponBulkIssueUseCase $bulkIssueUseCase;

    public function __construct(
        AdminCouponIndexUseCase $indexUseCase,
        AdminCouponCreateFormUseCase $createFormUseCase,
        AdminCouponStoreUseCase $storeUseCase,
        AdminCouponEditFormUseCase $editFormUseCase,
        AdminCouponUpdateUseCase $updateUseCase,
        AdminCouponDeleteUseCase $deleteUseCase,
        AdminCouponBulkIssueFormUseCase $bulkIssueFormUseCase,
        AdminCouponBulkIssueUseCase $bulkIssueUseCase,
        CouponAdminService $couponAdminService
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->createFormUseCase = $createFormUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->editFormUseCase = $editFormUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->deleteUseCase = $deleteUseCase;
        $this->bulkIssueFormUseCase = $bulkIssueFormUseCase;
        $this->bulkIssueUseCase = $bulkIssueUseCase;
        $this->couponAdminService = $couponAdminService;
    }

    public function coupons()
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->indexUseCase->handle($projectId);

        return view('admin.coupons.index', ['coupons' => $output->coupons]);
    }

    public function createCoupon()
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->createFormUseCase->handle($projectId);

        return view('admin.coupons.create', ['stampCards' => $output->stampCards]);
    }

    public function storeCoupon(CouponStoreRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        if ($request->hasFile('image')) {
            $validated['image_url'] = $this->couponAdminService->saveCouponImage($request->file('image'), $projectId);
        }
        unset($validated['image']);
        try {
            $this->storeUseCase->handle($projectId, $validated);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.coupons.create', $this->buildAdminRouteParams())
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを作成しました');
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

    public function updateCoupon(CouponUpdateRequest $request, string $projectKey, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        if ($request->hasFile('image')) {
            $validated['image_url'] = $this->couponAdminService->saveCouponImage($request->file('image'), $projectId);
        }
        unset($validated['image']);
        try {
            $this->updateUseCase->handle((int) $id, $validated, $projectId);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.coupons.edit', array_merge($this->buildAdminRouteParams(), ['id' => $id]))
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを更新しました');
    }

    public function deleteCoupon(string $projectKey, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $this->deleteUseCase->handle((int) $id, $projectId);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを削除しました');
    }

    /** プレゼント配信（クーポン一括配布）画面 */
    public function bulkIssueForm()
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->bulkIssueFormUseCase->handle($projectId);

        return view('admin.coupons.bulk-issue', [
            'coupons' => $output->coupons,
            'memberCount' => $output->memberCount,
        ]);
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
        $issued = $this->bulkIssueUseCase->handle($projectId, (int) $validated['coupon_id'], $memberIds);

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', "{$issued}件の会員にクーポンを配布しました");
    }
}
