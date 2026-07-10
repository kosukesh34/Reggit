<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;

class UpdateFeatureSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId, array $payload): void
    {
        $this->shopSettingRepository->setFeatureFlags($payload['features'] ?? [], $projectId);

        $this->shopSettingRepository->setValue('admin_sidebar_color', $payload['admin_sidebar_color'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('admin_header_color', $payload['admin_header_color'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('admin_primary_color', $payload['admin_primary_color'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('stamp_default_image_url', $payload['stamp_default_image_url'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('coupon_default_image_url', $payload['coupon_default_image_url'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('frontend_logo_url', $payload['frontend_logo_url'] ?? '', 'string', $projectId);
        $this->shopSettingRepository->setValue('frontend_header_text', $payload['frontend_header_text'] ?? '', 'string', $projectId);
    }
}
