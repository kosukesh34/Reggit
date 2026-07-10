<?php

namespace App\UseCases\Admin\Settings;

use App\Services\SettingsService;

class UpdateBasicFeatureFlagsUseCase
{
    public function __construct(
        private SettingsService $settingsService,
    ) {}

    /** @param array<string, bool> $featureFlags */
    public function handle(int $projectId, array $featureFlags): void
    {
        $this->settingsService->updateFeatureFlags($featureFlags, $projectId);
    }
}
