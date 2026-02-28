<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\ShopSetting;
use Illuminate\Http\Request;

class AnnouncementsController extends AdminBaseController
{
    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $featureEnabled = ShopSetting::isFeatureEnabled('feature_announcements_enabled', $projectId);
        $defaultPinned = (bool) ShopSetting::getValue('announcement_default_pinned', false, $projectId);
        $defaultExpiryDays = (int) ShopSetting::getValue('announcement_default_expiry_days', 0, $projectId);
        return view('admin.settings.announcements', compact('featureEnabled', 'defaultPinned', 'defaultExpiryDays'));
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'feature_announcements_enabled' => 'nullable|boolean',
            'announcement_default_pinned' => 'nullable|boolean',
            'announcement_default_expiry_days' => 'nullable|integer|min:0|max:365',
        ]);
        $flags = ShopSetting::getFeatureFlags($projectId);
        $flags['feature_announcements_enabled'] = (bool) ($validated['feature_announcements_enabled'] ?? false);
        ShopSetting::setFeatureFlags($flags, $projectId);
        ShopSetting::setValue('announcement_default_pinned', isset($validated['announcement_default_pinned']) ? '1' : '0', 'boolean', $projectId);
        ShopSetting::setValue('announcement_default_expiry_days', (int) ($validated['announcement_default_expiry_days'] ?? 0), 'integer', $projectId);
        return redirect()->route('admin.settings.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせの設定を更新しました');
    }
}
