<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class UpdateLiffDesignerUseCase
{
    /**
     * @param array<string, mixed> $layouts
     * @param array<string, mixed>|null $tabSettings
     */
    public function handle(
        int $projectId,
        array $layouts,
        string $customCss,
        ?array $tabSettings
    ): void {
        ShopSetting::setLiffTabLayouts($layouts, $projectId);
        ShopSetting::setLiffCustomCss($customCss, $projectId);

        if ($tabSettings !== null) {
            ShopSetting::setLiffTabSettings($tabSettings, $projectId);
        }
    }
}
