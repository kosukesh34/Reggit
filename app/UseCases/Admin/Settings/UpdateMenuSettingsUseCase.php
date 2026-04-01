<?php

namespace App\UseCases\Admin\Settings;

use App\Services\SettingsService;

class UpdateMenuSettingsUseCase
{
    private SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /** @param array<string, bool|int|float|string|array|object|null> $validated */
    public function handle(int $projectId, array $validated): void
    {
        $this->settingsService->updateMenuSettings($validated, $projectId);
    }
}
