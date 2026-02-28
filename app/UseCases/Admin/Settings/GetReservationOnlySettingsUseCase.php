<?php

namespace App\UseCases\Admin\Settings;

use App\Services\SettingsService;

class GetReservationOnlySettingsUseCase
{
    private SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function handle(int $projectId): array
    {
        return $this->settingsService->getReservationOnlySettings($projectId);
    }
}
