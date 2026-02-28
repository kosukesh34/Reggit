<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Services\SettingsService;
use App\UseCases\Admin\Settings\Data\BasicSettingsView;

class GetBasicSettingsUseCase
{
    private SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function handle(int $projectId): BasicSettingsView
    {
        $settings = $this->settingsService->getBasicSettings($projectId);
        $featureFlags = ShopSetting::getFeatureFlags($projectId);

        return new BasicSettingsView($settings, $featureFlags);
    }
}
