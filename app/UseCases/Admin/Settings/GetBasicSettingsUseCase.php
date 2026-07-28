<?php

namespace App\UseCases\Admin\Settings;

use App\Services\SettingsService;
use App\UseCases\Admin\Settings\Data\BasicSettingsView;

class GetBasicSettingsUseCase
{
    public function __construct(
        private SettingsService $settingsService,
    ) {}

    public function handle(int $projectId): BasicSettingsView
    {
        return new BasicSettingsView($this->settingsService->getBasicSettings($projectId));
    }
}
