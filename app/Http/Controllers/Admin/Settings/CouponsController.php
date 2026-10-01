<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetCouponsSettingsUseCase;

class CouponsController extends AdminBaseController
{
    public function __construct(private GetCouponsSettingsUseCase $getCouponsSettingsUseCase) {}

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getCouponsSettingsUseCase->handle($projectId);

        return view('admin.settings.coupons', [
            'couponDefaultImageUrl' => $view->couponDefaultImageUrl,
            'featureCouponsEnabled' => $view->featureCouponsEnabled,
        ]);
    }

    public function update()
    {
        return redirect()->route('admin.settings.app-branding', $this->buildAdminRouteParams())
            ->with('info', 'クーポン画像は「店舗名・画像」、利用状況は「利用機能」から変更できます。');
    }
}
