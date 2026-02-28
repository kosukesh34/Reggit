<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetCouponsSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateCouponsSettingsUseCase;
use Illuminate\Http\Request;

class CouponsController extends AdminBaseController
{
    private GetCouponsSettingsUseCase $getCouponsSettingsUseCase;
    private UpdateCouponsSettingsUseCase $updateCouponsSettingsUseCase;

    public function __construct(
        GetCouponsSettingsUseCase $getCouponsSettingsUseCase,
        UpdateCouponsSettingsUseCase $updateCouponsSettingsUseCase
    ) {
        $this->getCouponsSettingsUseCase = $getCouponsSettingsUseCase;
        $this->updateCouponsSettingsUseCase = $updateCouponsSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getCouponsSettingsUseCase->handle($projectId);

        return view('admin.settings.coupons', [
            'couponDefaultImageUrl' => $view->couponDefaultImageUrl,
            'featureCouponsEnabled' => $view->featureCouponsEnabled,
        ]);
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'coupon_default_image_url' => 'nullable|url|max:500',
            'feature_coupons_enabled' => 'nullable|boolean',
        ]);
        $this->updateCouponsSettingsUseCase->handle($projectId, $validated);

        return redirect()->route('admin.settings.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポン設定を更新しました');
    }
}
