<?php

namespace App\UseCases\Admin\Settings\Dto;

class FeatureSettingsViewDto
{
    public function __construct(
        public array $featureFlags,
        public array $featureFlagLabels,
        public ?string $adminSidebarColor,
        public ?string $adminHeaderColor,
        public ?string $adminPrimaryColor,
        public ?string $stampDefaultImageUrl,
        public ?string $couponDefaultImageUrl,
        public ?string $frontendLogoUrl,
        public ?string $frontendHeaderText
    ) {
    }
}
