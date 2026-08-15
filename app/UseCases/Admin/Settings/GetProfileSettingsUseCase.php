<?php

namespace App\UseCases\Admin\Settings;

use App\Enums\MemberRegistrationMode;
use App\Enums\ProfileCustomFieldInputType;
use App\Services\SettingsService;
use App\UseCases\Admin\Settings\Data\ProfileSettingsView;

class GetProfileSettingsUseCase
{
    private SettingsService $settingsService;

    public function __construct(
        SettingsService $settingsService
    ) {
        $this->settingsService = $settingsService;
    }

    public function handle(int $projectId): ProfileSettingsView
    {
        return new ProfileSettingsView(
            $this->settingsService->getProfileSettings($projectId),
            MemberRegistrationMode::getValues(),
            ProfileCustomFieldInputType::getValues(),
        );
    }
}
