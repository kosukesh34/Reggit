<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\ShopSetting;
use Illuminate\Http\Request;

class CouponsController extends AdminBaseController
{
    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $couponDefaultImageUrl = ShopSetting::getCouponDefaultImageUrl($projectId);
        $featureCouponsEnabled = ShopSetting::isFeatureEnabled('feature_coupons_enabled', $projectId);
        return view('admin.settings.coupons', compact('couponDefaultImageUrl', 'featureCouponsEnabled'));
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'coupon_default_image_url' => 'nullable|url|max:500',
            'feature_coupons_enabled' => 'nullable|boolean',
        ]);
        ShopSetting::setValue('coupon_default_image_url', $validated['coupon_default_image_url'] ?? '', 'string', $projectId);
        $flags = ShopSetting::getFeatureFlags($projectId);
        $flags['feature_coupons_enabled'] = (bool) ($validated['feature_coupons_enabled'] ?? false);
        ShopSetting::setFeatureFlags($flags, $projectId);
        return redirect()->route('admin.settings.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポン設定を更新しました');
    }
}
