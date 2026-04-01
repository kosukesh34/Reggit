<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class UpdateCouponsSettingsUseCase
{
    /** @param array<string, bool|int|float|string|array|object|null> $validated */
    public function handle(int $projectId, array $validated): void
    {
        ShopSetting::setValue('coupon_default_image_url', $validated['coupon_default_image_url'] ?? '', 'string', $projectId);
        $flags = ShopSetting::getFeatureFlags($projectId);
        $flags['feature_coupons_enabled'] = (bool) ($validated['feature_coupons_enabled'] ?? false);
        ShopSetting::setFeatureFlags($flags, $projectId);
    }
}
