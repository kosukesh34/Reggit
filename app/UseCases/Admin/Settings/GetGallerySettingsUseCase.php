<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class GetGallerySettingsUseCase
{
    public function handle(int $projectId): array
    {
        $featureEnabled = ShopSetting::isFeatureEnabled('feature_gallery_enabled', $projectId);

        return ['featureEnabled' => $featureEnabled];
    }
}
