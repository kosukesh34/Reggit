<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;

class UpdateGallerySettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated): void
    {
        $flags = $this->shopSettingRepository->getFeatureFlags($projectId);
        $flags['feature_gallery_enabled'] = (bool) ($validated['feature_gallery_enabled'] ?? false);
        $this->shopSettingRepository->setFeatureFlags($flags, $projectId);
    }
}
