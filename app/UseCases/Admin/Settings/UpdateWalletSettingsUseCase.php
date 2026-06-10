<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Services\Admin\WalletAssetService;
use App\UseCases\Admin\Settings\Data\UpdateWalletSettingsInput;

/**
 * Wallet 画像設定の更新。
 * 戻り値の messages はリダイレクト時の success メッセージ用。
 */
class UpdateWalletSettingsUseCase
{
    public function __construct(
        private WalletAssetService $walletAssetService,
    ) {}

    /** @return array<int, string> */
    public function handle(int $projectId, UpdateWalletSettingsInput $input): array
    {
        $messages = [];

        if ($input->hasAppleFiles()) {
            $saved = $this->walletAssetService->saveAppleAssets($projectId, $input->appleIcon, $input->appleLogo);
            if ($saved) {
                $messages[] = 'Apple Wallet の画像を更新しました。';
            }
        }

        if ($input->googleProgramLogoFile !== null) {
            $url = $this->walletAssetService->saveGoogleAsset($projectId, $input->googleProgramLogoFile, 'program_logo');
            if ($url !== null) {
                ShopSetting::setWalletGoogleProgramLogoUrl($url, $projectId);
                $messages[] = 'Google Wallet プログラムロゴを更新しました。';
            }
        }
        if ($input->googleHeroFile !== null) {
            $url = $this->walletAssetService->saveGoogleAsset($projectId, $input->googleHeroFile, 'hero');
            if ($url !== null) {
                ShopSetting::setWalletGoogleHeroImageUrl($url, $projectId);
                $messages[] = 'Google Wallet ヒーロー画像を更新しました。';
            }
        }

        if ($input->googleProgramLogoFile === null && $input->hasGoogleProgramLogoUrlKey) {
            ShopSetting::setWalletGoogleProgramLogoUrl($input->googleProgramLogoUrl ?: null, $projectId);
        }
        if ($input->googleHeroFile === null && $input->hasGoogleHeroImageUrlKey) {
            ShopSetting::setWalletGoogleHeroImageUrl($input->googleHeroImageUrl ?: null, $projectId);
        }

        return $messages;
    }
}
