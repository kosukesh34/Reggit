<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CouponBulkIssueRequest;
use App\Http\Requests\Admin\CouponQuickCreateRequest;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use App\UseCases\Admin\Coupon\AdminCouponStoreUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class CouponBulkIssueController extends AdminBaseController
{
    public function __construct(
        private AdminCouponBulkIssueFormUseCase $bulkIssueFormUseCase,
        private AdminCouponBulkIssueUseCase $bulkIssueUseCase,
        private AdminCouponStoreUseCase $storeUseCase,
    ) {}

    public function create(): View
    {
        $output = $this->bulkIssueFormUseCase->handle($this->resolveAdminProjectId());

        return view('admin.coupons.bulk-issue', ['view' => $output]);
    }

    public function quickCreate(CouponQuickCreateRequest $request): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();

        try {
            $coupon = $this->storeUseCase->handle($projectId, $request->validated(), true);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.coupons.bulk-issue', array_merge(
            $this->buildAdminRouteParams(),
            ['coupon_source' => 'existing'],
        ))
            ->with('success', 'クーポンを作成しました'.($coupon->smaregi_coupon_id ? '（スマレジにも反映済み）' : '').'。配布設定に進んでください。')
            ->withInput(['coupon_id' => $coupon->id, 'coupon_source' => 'existing']);
    }

    public function store(CouponBulkIssueRequest $request): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();

        try {
            $result = $this->bulkIssueUseCase->handle($projectId, (int) $validated['coupon_id'], $validated);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', $result['message']);
    }
}
