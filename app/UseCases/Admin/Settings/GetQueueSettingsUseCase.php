<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class GetQueueSettingsUseCase
{
    public function handle(int $projectId): array
    {
        $avgServeMinutes = ShopSetting::getQueueAvgServeMinutes($projectId);

        return ['avgServeMinutes' => $avgServeMinutes];
    }
}
