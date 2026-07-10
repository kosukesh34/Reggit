<?php

namespace App\UseCases\Admin\Settings;

use App\Services\SettingsService;

class GetProfileSettingsUseCase
{
    public function __construct(
        private SettingsService $settingsService,
    ) {}

    public function handle(int $projectId): array
    {
        return $this->settingsService->getProfileSettings($projectId);
    }
}
