<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Services\Admin\LiffSettingsService;
use App\UseCases\Admin\Settings\Data\UpdateLiffDesignerInput;

class UpdateLiffDesignerUseCase
{
    private const MEMBER_CARD_DESIGNS = [
        'default',
        'logo_top',
        'logo_center',
        'logo_header',
        'compact',
        'split_banner',
        'minimal_line',
        'emblem',
    ];

    public function __construct(
        private LiffSettingsService $liffSettingsService,
    ) {}

    public function handle(int $projectId, UpdateLiffDesignerInput $input): void
    {
        $this->liffSettingsService->setTabLayouts($input->layouts, $projectId);
        if ($input->detailLayouts !== null) {
            $this->liffSettingsService->setDetailLayouts($input->detailLayouts, $projectId);
        }
        $this->liffSettingsService->setCustomCss($input->customCss, $projectId);

        if ($input->tabSettings !== null) {
            $this->liffSettingsService->setTabSettings($input->tabSettings, $projectId);
            $this->syncMemberCardSettingsFromGlobal($input->tabSettings, $projectId);
        }

        if ($input->menuItems !== null && $input->menuItems !== []) {
            $this->liffSettingsService->setFrontendMenuItems($input->menuItems, $projectId);
        }
    }

    /**
     * @param  array<string, mixed>  $tabSettings
     */
    private function syncMemberCardSettingsFromGlobal(array $tabSettings, int $projectId): void
    {
        $global = $tabSettings['_global'] ?? null;
        if (! is_array($global)) {
            return;
        }

        $design = (string) ($global['member_card_design'] ?? '');
        if (in_array($design, self::MEMBER_CARD_DESIGNS, true)) {
            ShopSetting::setMemberCardDesign($design, $projectId);
        }

        $displayType = (string) ($global['member_card_display_type'] ?? '');
        if (in_array($displayType, ['card', 'sheet'], true)) {
            ShopSetting::setMemberCardDisplayType($displayType, $projectId);
        }

        $codeType = (string) ($global['member_card_code_type'] ?? '');
        if (in_array($codeType, ['barcode', 'qrcode'], true)) {
            ShopSetting::setMemberCardCodeType($codeType, $projectId);
        }
    }
}
