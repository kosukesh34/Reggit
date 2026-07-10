<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;

class UpdateCouponsSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated): void
    {
        $this->shopSettingRepository->setValue('coupon_default_image_url', $validated['coupon_default_image_url'] ?? '', 'string', $projectId);
        $flags = $this->shopSettingRepository->getFeatureFlags($projectId);
        $flags['feature_coupons_enabled'] = (bool) ($validated['feature_coupons_enabled'] ?? false);
        $this->shopSettingRepository->setFeatureFlags($flags, $projectId);
    }
}
