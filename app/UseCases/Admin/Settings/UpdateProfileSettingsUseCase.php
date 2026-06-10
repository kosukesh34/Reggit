<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\UpdateProfileSettingsInput;

class UpdateProfileSettingsUseCase
{
    public function handle(int $projectId, UpdateProfileSettingsInput $input): void
    {
        ShopSetting::setProfileFieldSettings([
            'smaregi_fields' => $input->smaregiFields,
            'custom_fields' => $input->customFields,
        ], $projectId);
    }
}
