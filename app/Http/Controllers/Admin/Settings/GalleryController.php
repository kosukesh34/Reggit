<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\ShopSetting;
use Illuminate\Http\Request;

class GalleryController extends AdminBaseController
{
    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $featureEnabled = ShopSetting::isFeatureEnabled('feature_gallery_enabled', $projectId);
        return view('admin.settings.gallery', compact('featureEnabled'));
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'feature_gallery_enabled' => 'nullable|boolean',
        ]);
        $flags = ShopSetting::getFeatureFlags($projectId);
        $flags['feature_gallery_enabled'] = (bool) ($validated['feature_gallery_enabled'] ?? false);
        ShopSetting::setFeatureFlags($flags, $projectId);
        return redirect()->route('admin.settings.gallery', $this->buildAdminRouteParams())
            ->with('success', 'ギャラリー設定を更新しました');
    }
}
