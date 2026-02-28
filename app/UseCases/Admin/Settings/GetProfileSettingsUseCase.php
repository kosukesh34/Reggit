<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class GetProfileSettingsUseCase
{
    public function handle(int $projectId): array
    {
        return ShopSetting::getProfileFieldSettings($projectId);
    }
}
