<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Services\SettingsService;
use App\UseCases\Admin\Settings\Data\BasicSettingsView;

class GetBasicSettingsUseCase
{
    public function __construct(
        private SettingsService $settingsService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): BasicSettingsView
    {
        $settings = $this->settingsService->getBasicSettings($projectId);
        $featureFlags = $this->shopSettingRepository->getFeatureFlags($projectId);

        return new BasicSettingsView($settings, $featureFlags);
    }
}
