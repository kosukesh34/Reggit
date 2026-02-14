<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class UpdateFeatureSettingsUseCase
{
    public function handle(int $projectId, array $payload): void
    {
        ShopSetting::setFeatureFlags($payload['features'] ?? [], $projectId);

        ShopSetting::setValue('admin_sidebar_color', $payload['admin_sidebar_color'] ?? '', 'string', $projectId);
        ShopSetting::setValue('admin_header_color', $payload['admin_header_color'] ?? '', 'string', $projectId);
        ShopSetting::setValue('admin_primary_color', $payload['admin_primary_color'] ?? '', 'string', $projectId);
        ShopSetting::setValue('stamp_default_image_url', $payload['stamp_default_image_url'] ?? '', 'string', $projectId);
        ShopSetting::setValue('coupon_default_image_url', $payload['coupon_default_image_url'] ?? '', 'string', $projectId);
        ShopSetting::setValue('frontend_logo_url', $payload['frontend_logo_url'] ?? '', 'string', $projectId);
        ShopSetting::setValue('frontend_header_text', $payload['frontend_header_text'] ?? '', 'string', $projectId);
    }
}
