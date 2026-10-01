<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\SettingValueReader;
use App\Support\HomeImageUrls;
use App\UseCases\Admin\Settings\Data\AppImagesSettingsView;

class GetAppImagesSettingsUseCase
{
    public function __construct(private SettingValueReader $settings) {}

    public function handle(int $projectId): AppImagesSettingsView
    {
        return new AppImagesSettingsView(
            (string) $this->settings->getValue('frontend_header_text', '', $projectId),
            (string) $this->settings->getValue('frontend_logo_url', '', $projectId),
            (string) $this->settings->getValue('stamp_default_image_url', '', $projectId),
            (string) $this->settings->getValue('coupon_default_image_url', '', $projectId),
            (string) $this->settings->getValue('navigation_header_image_url', '', $projectId),
            HomeImageUrls::forProject($this->settings, $projectId),
        );
    }
}
