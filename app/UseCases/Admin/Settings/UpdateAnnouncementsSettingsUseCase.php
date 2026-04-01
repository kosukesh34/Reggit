<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class UpdateAnnouncementsSettingsUseCase
{
    /** @param array<string, bool|int|float|string|array|object|null> $validated */
    public function handle(int $projectId, array $validated): void
    {
        $flags = ShopSetting::getFeatureFlags($projectId);
        $flags['feature_announcements_enabled'] = (bool) ($validated['feature_announcements_enabled'] ?? false);
        ShopSetting::setFeatureFlags($flags, $projectId);
        ShopSetting::setValue('announcement_default_pinned', isset($validated['announcement_default_pinned']) ? '1' : '0', 'boolean', $projectId);
        ShopSetting::setValue('announcement_default_expiry_days', (int) ($validated['announcement_default_expiry_days'] ?? 0), 'integer', $projectId);
    }
}
