<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class UpdateBasicFeatureFlagsUseCase
{
    /** @param array<string, bool> $featureFlags */
    public function handle(int $projectId, array $featureFlags): void
    {
        ShopSetting::setFeatureFlags($featureFlags, $projectId);
    }
}
