<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponDeliveryType;
use App\Services\Admin\AnalysisApiSettingsService;
use App\UseCases\Admin\Analysis\AdminAnalysisCouponIndexUseCase;
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
        private AdminAnalysisCouponIndexUseCase $couponIndexUseCase,
        private AnalysisApiSettingsService $analysisApiSettings,
    ) {}

    public function index(): View
    {
        $projectId = $this->resolveAdminProjectId();
        $coupons = $this->couponIndexUseCase->handle($projectId);

        return view('admin.analysis-delivery', [
            'coupons' => $coupons,
            'projectKey' => $this->resolveAdminProjectKey(),
            'project' => $this->analysisApiSettings->project($projectId),
            'analysisTokens' => $this->analysisApiSettings->activeTokens($projectId),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('analysis_api_enabled');
        $this->analysisApiSettings->update($this->resolveAdminProjectId(), $enabled, $request->boolean('analysis_delivery_enabled'));

        return redirect()->route('admin.analysis-deliveries', $this->buildAdminRouteParams())
            ->with('success', '分析連携の設定を更新しました');
    }

    public function issueToken(): RedirectResponse
    {
        $plain = $this->analysisApiSettings->issueToken($this->resolveAdminProjectId());

        return redirect()->route('admin.analysis-deliveries', $this->buildAdminRouteParams())
            ->with('new_analysis_token', $plain);
    }

    public function revokeToken(string $project_key, int $token): RedirectResponse
    {
        $this->analysisApiSettings->revokeToken($this->resolveAdminProjectId(), $token);

        return redirect()->route('admin.analysis-deliveries', $this->buildAdminRouteParams())
            ->with('success', '分析用トークンを失効しました');
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
