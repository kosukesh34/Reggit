<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\MemberRegistrationMode;
use App\Services\Admin\LiffSettingsService;
use App\Support\LiffThemePresets;
use App\Support\MemberCardDesignMap;
use App\UseCases\Admin\Settings\Data\UpdateLiffDesignerInput;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateLiffDesignerUseCase
{
    public function __construct(
        private LiffSettingsService $liffSettingsService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId, UpdateLiffDesignerInput $input): void
    {
        $this->liffSettingsService->setTabLayouts($input->layouts, $projectId);
        if ($input->detailLayouts !== null) {
            $this->liffSettingsService->setDetailLayouts($input->detailLayouts, $projectId);
        }
        $this->liffSettingsService->setCustomCss($input->customCss, $projectId);

        if ($input->tabSettings !== null && $input->tabSettings !== []) {
            $this->liffSettingsService->setTabSettings($input->tabSettings, $projectId);
            try {
                $this->syncRuntimeSettingsFromGlobal($input->tabSettings, $projectId);
            } catch (Throwable $e) {
                // 本体保存は成功させる。同期失敗はログのみ。
                Log::warning('[LIFF Designer] runtime settings sync failed', [
                    'project_id' => $projectId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($input->menuItems !== null && $input->menuItems !== []) {
            $this->liffSettingsService->setFrontendMenuItems($input->menuItems, $projectId);
        }
    }

    /**
     * Designer の _global を、エンドユーザー画面が読む既存設定へ同期する。
     *
     * @param  array<string, mixed>  $tabSettings
     */
    private function syncRuntimeSettingsFromGlobal(array $tabSettings, int $projectId): void
    {
        $global = $tabSettings['_global'] ?? null;
        if (! is_array($global)) {
            return;
        }

        $design = (string) ($global['member_card_design'] ?? '');
        if (MemberCardDesignMap::isValidDesign($design)) {
            $this->shopSettingRepository->setMemberCardDesign($design, $projectId);
        }

        $displayType = (string) ($global['member_card_display_type'] ?? '');
        if (in_array($displayType, ['card', 'sheet'], true)) {
            $this->shopSettingRepository->setMemberCardDisplayType($displayType, $projectId);
        }

        $codeType = (string) ($global['member_card_code_type'] ?? '');
        if (in_array($codeType, ['barcode', 'qrcode'], true)) {
            $this->shopSettingRepository->setMemberCardCodeType($codeType, $projectId);
        }

        $appDisplayName = trim((string) ($global['app_display_name'] ?? ''));
        if ($appDisplayName !== '') {
            $this->shopSettingRepository->setValue('frontend_header_text', $appDisplayName, 'string', $projectId);
        }

        if (array_key_exists('require_membership', $global)) {
            $isRequired = filter_var($global['require_membership'], FILTER_VALIDATE_BOOLEAN);
            $this->shopSettingRepository->setMemberRegistrationMode(
                $isRequired ? MemberRegistrationMode::FORCE : MemberRegistrationMode::OPTIONAL,
                $projectId
            );
        }

        $themeVars = LiffThemePresets::cssVariablesFor($global);
        $primary = (string) ($themeVars['--primary-color'] ?? '');
        $primaryDark = (string) ($themeVars['--primary-dark'] ?? '');
        if ($primary !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $primary) === 1) {
            $this->shopSettingRepository->setValue('line_primary_color', $primary, 'string', $projectId);
        }
        if ($primaryDark !== '' && preg_match('/^#[0-9a-fA-F]{6}$/', $primaryDark) === 1) {
            $this->shopSettingRepository->setValue('line_primary_dark', $primaryDark, 'string', $projectId);
        }
    }
}
