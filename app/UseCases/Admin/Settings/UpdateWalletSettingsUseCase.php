<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\WalletSettings;
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
        private WalletSettings $walletSettings,
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
        if ($input->walletAppleIcon === null && $input->clearAppleIcon) {
            $this->walletAssetService->clearAppleIcon($input->projectId);
            $messages[] = 'Apple Wallet アイコンをクリアしました。';
        }
        if ($input->walletAppleLogo === null && $input->clearAppleLogo) {
            $this->walletAssetService->clearAppleLogo($input->projectId);
            $messages[] = 'Apple Wallet ロゴをクリアしました。';
        }

        if ($input->walletGoogleProgramLogoFile !== null) {
            $url = $this->walletAssetService->saveGoogleAsset(
                $input->projectId,
                $input->walletGoogleProgramLogoFile,
                'program_logo',
            );
            if ($url !== null) {
                $this->walletSettings->setWalletGoogleProgramLogoUrl($url, $input->projectId);
                $messages[] = 'Google Wallet プログラムロゴを更新しました。';
            }
        } elseif ($input->clearGoogleProgramLogo) {
            $this->walletSettings->setWalletGoogleProgramLogoUrl(null, $input->projectId);
            $messages[] = 'Google Wallet プログラムロゴをクリアしました。';
        }

        if ($input->walletGoogleHeroFile !== null) {
            $url = $this->walletAssetService->saveGoogleAsset(
                $input->projectId,
                $input->walletGoogleHeroFile,
                'hero',
            );
            if ($url !== null) {
                $this->walletSettings->setWalletGoogleHeroImageUrl($url, $input->projectId);
                $messages[] = 'Google Wallet ヒーロー画像を更新しました。';
            }
        } elseif ($input->clearGoogleHero) {
            $this->walletSettings->setWalletGoogleHeroImageUrl(null, $input->projectId);
            $messages[] = 'Google Wallet ヒーロー画像をクリアしました。';
        }

        return $messages;
    }
}
