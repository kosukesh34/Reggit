<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ExistingMemberLinkSetting;

class GetExistingMemberLinkSettingUseCase
{
    public function handle(int $projectId): ?ExistingMemberLinkSetting
    {
        return ExistingMemberLinkSetting::query()->where('project_id', $projectId)->first();
    }
}
