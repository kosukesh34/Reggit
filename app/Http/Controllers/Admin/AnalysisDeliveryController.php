<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use App\Util\LogHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class AnalysisDeliveryController extends AdminBaseController
{
    public function __construct(
        private AdminCouponBulkIssueUseCase $adminCouponBulkIssueUseCase,
    ) {}

    public function index(): View
    {
        $projectId = $this->resolveAdminProjectId();
        $coupons = Coupon::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.analysis-delivery', [
            'coupons' => $coupons,
            'projectKey' => $this->resolveAdminProjectKey(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'coupon_id' => ['required', 'integer'],
            'send_notification' => ['sometimes', 'boolean'],
        ]);
        $projectId = $this->resolveAdminProjectId();
        $couponId = (int) $validated['coupon_id'];

        try {
            $result = $this->adminCouponBulkIssueUseCase->handle($projectId, $couponId, [
                'delivery_type' => CouponDeliveryType::IMMEDIATE_ALL,
                'send_notification' => $request->boolean('send_notification'),
            ]);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('admin.analysis-deliveries', $this->buildAdminRouteParams())
                ->withErrors(['coupon_id' => $e->getMessage()]);
        } catch (\Throwable $e) {
            LogHelper::error('[Analysis] admin delivery failed', [
                'project_id' => $projectId,
                'coupon_id' => $couponId,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.analysis-deliveries', $this->buildAdminRouteParams())
                ->withErrors(['coupon_id' => '配信の実行に失敗しました']);
        }

        return redirect()
            ->route('admin.analysis-deliveries', $this->buildAdminRouteParams())
            ->with('success', $result['message']);
    }
}
