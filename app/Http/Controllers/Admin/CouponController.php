<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CouponBulkIssueRequest;
use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use App\UseCases\Admin\Coupon\AdminCouponCreateFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeleteUseCase;
use App\UseCases\Admin\Coupon\AdminCouponEditFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponIndexUseCase;
use App\UseCases\Admin\Coupon\AdminCouponStoreUseCase;
use App\UseCases\Admin\Coupon\AdminCouponUpdateUseCase;
use Illuminate\Http\Request;

class CouponController extends AdminBaseController
{
    private AdminCouponIndexUseCase $indexUseCase;
    private AdminCouponCreateFormUseCase $createFormUseCase;
    private AdminCouponStoreUseCase $storeUseCase;
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
        AdminCouponBulkIssueUseCase $bulkIssueUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->createFormUseCase = $createFormUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->editFormUseCase = $editFormUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->deleteUseCase = $deleteUseCase;
        $this->bulkIssueFormUseCase = $bulkIssueFormUseCase;
        $this->bulkIssueUseCase = $bulkIssueUseCase;
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
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを作成しました');
    }

    /** @param string|int $id */
    public function editCoupon($id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->editFormUseCase->handle($projectId, (int) $id);

        return view('admin.coupons.edit', [
            'coupon' => $output->coupon,
            'stampCards' => $output->stampCards,
        ]);
    }

    /** @param string|int $id */
    public function updateCoupon(CouponUpdateRequest $request, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->updateUseCase->handle((int) $id, $request->validated(), $projectId);

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを更新しました');
    }

    /** @param string|int $id */
    public function deleteCoupon($id)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->deleteUseCase->handle((int) $id, $projectId);

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
