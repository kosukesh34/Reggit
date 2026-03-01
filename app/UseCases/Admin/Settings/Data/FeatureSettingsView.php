<?php

namespace App\UseCases\Admin\Settings\Data;

/** 機能設定画面用の表示データ。 */
class FeatureSettingsView
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
    ) {}
}
