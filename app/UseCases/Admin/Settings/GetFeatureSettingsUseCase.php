<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\UseCases\Admin\Settings\Data\FeatureSettingsView;

class GetFeatureSettingsUseCase
{
    public function __construct(
        private FeatureSettings $shopSettingRepository,
    ) {}

    public function handle(int $projectId): FeatureSettingsView
    {
        return new FeatureSettingsView(
            $this->shopSettingRepository->getFeatureFlags($projectId),
            $this->shopSettingRepository->getFeatureFlagLabels(),
            (bool) $this->shopSettingRepository->getValue('use_points', true, $projectId),
            (bool) $this->shopSettingRepository->getValue('use_transaction_stamps', true, $projectId),
            (string) $this->shopSettingRepository->getValue('point_usage_scope', 'app', $projectId),
        );
    }
}
