<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\AdminSavePublicImageUseCase;
use App\UseCases\Admin\Settings\GetCouponsSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateCouponsSettingsUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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
            'coupon_default_image' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp|max:2048',
            'coupon_default_image_clear' => 'nullable|in:0,1',
            'feature_coupons_enabled' => 'nullable|boolean',
        ]);
        $imageUrl = (string) ($this->getCouponsSettingsUseCase->handle($projectId)->couponDefaultImageUrl ?? '');
        $imageFile = $request->file('coupon_default_image');
        if ($imageFile instanceof UploadedFile) {
            $imageUrl = resolve(AdminSavePublicImageUseCase::class)->handle(
                $imageFile,
                $projectId,
                PublicImageStorageService::DIR_COUPON_DEFAULT,
            );
        } elseif ($request->input('coupon_default_image_clear') === '1') {
            $imageUrl = '';
        }
        $this->updateCouponsSettingsUseCase->handle($projectId, [
            'coupon_default_image_url' => $imageUrl,
            'feature_coupons_enabled' => $validated['feature_coupons_enabled'] ?? false,
        ]);

        return redirect()->route('admin.settings.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポン設定を更新しました');
    }
}
