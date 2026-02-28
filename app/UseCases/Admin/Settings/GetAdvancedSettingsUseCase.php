<?php

namespace App\UseCases\Admin\Settings;

use App\Services\SettingsService;
use App\UseCases\Admin\Settings\Data\AdvancedSettingsView;

class GetAdvancedSettingsUseCase
{
    private SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function handle(int $projectId): AdvancedSettingsView
    {
        $settings = $this->settingsService->getAdvancedSettings($projectId);

        return new AdvancedSettingsView($settings);
    }
}
