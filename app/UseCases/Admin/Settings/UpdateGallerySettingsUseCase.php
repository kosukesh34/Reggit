<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class UpdateGallerySettingsUseCase
{
    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated): void
    {
        $flags = ShopSetting::getFeatureFlags($projectId);
        $flags['feature_gallery_enabled'] = (bool) ($validated['feature_gallery_enabled'] ?? false);
        ShopSetting::setFeatureFlags($flags, $projectId);
    }
}
