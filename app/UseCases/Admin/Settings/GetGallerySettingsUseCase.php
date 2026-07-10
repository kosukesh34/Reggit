<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;

class GetGallerySettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): array
    {
        $featureEnabled = $this->shopSettingRepository->isFeatureEnabled('feature_gallery_enabled', $projectId);

        return ['featureEnabled' => $featureEnabled];
    }
}
