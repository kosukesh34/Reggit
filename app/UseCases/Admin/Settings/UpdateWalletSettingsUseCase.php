<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
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
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /**
     * @return array<int, string>
     */
    public function handle(UpdateWalletSettingsInput $input): array
    {
        $messages = [];

        if ($input->walletAppleIcon !== null || $input->walletAppleLogo !== null) {
            $saved = $this->walletAssetService->saveAppleAssets(
                $input->projectId,
                $input->walletAppleIcon,
                $input->walletAppleLogo,
            );
            if ($saved) {
                $messages[] = 'Apple Wallet の画像を更新しました。';
            }
        }

        if ($input->walletGoogleProgramLogoFile !== null) {
            $url = $this->walletAssetService->saveGoogleAsset(
                $input->projectId,
                $input->walletGoogleProgramLogoFile,
                'program_logo',
            );
            if ($url !== null) {
                $this->shopSettingRepository->setWalletGoogleProgramLogoUrl($url, $input->projectId);
                $messages[] = 'Google Wallet プログラムロゴを更新しました。';
            }
        }
        if ($input->walletGoogleHeroFile !== null) {
            $url = $this->walletAssetService->saveGoogleAsset(
                $input->projectId,
                $input->walletGoogleHeroFile,
                'hero',
            );
            if ($url !== null) {
                $this->shopSettingRepository->setWalletGoogleHeroImageUrl($url, $input->projectId);
                $messages[] = 'Google Wallet ヒーロー画像を更新しました。';
            }
        }

        if ($input->walletGoogleProgramLogoFile === null && $input->hasWalletGoogleProgramLogoUrlField) {
            $this->shopSettingRepository->setWalletGoogleProgramLogoUrl(
                $input->walletGoogleProgramLogoUrl !== '' ? $input->walletGoogleProgramLogoUrl : null,
                $input->projectId,
            );
        }
        if ($input->walletGoogleHeroFile === null && $input->hasWalletGoogleHeroImageUrlField) {
            $this->shopSettingRepository->setWalletGoogleHeroImageUrl(
                $input->walletGoogleHeroImageUrl !== '' ? $input->walletGoogleHeroImageUrl : null,
                $input->projectId,
            );
        }

        return $messages;
    }
}
