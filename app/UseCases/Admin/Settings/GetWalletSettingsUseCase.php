<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\WalletSettings;
use App\Services\Admin\WalletAssetService;
use App\UseCases\Admin\Settings\Data\WalletSettingsView;

class GetWalletSettingsUseCase
{
    public function __construct(
        private WalletAssetService $walletAssetService,
        private WalletSettings $walletSettings,
    ) {}

    public function handle(int $projectId): WalletSettingsView
    {
        return new WalletSettingsView(
            $this->walletSettings->getWalletGoogleProgramLogoUrl($projectId),
            $this->walletSettings->getWalletGoogleHeroImageUrl($projectId),
            $this->walletAssetService->hasAppleAssets($projectId),
            $this->walletAssetService->getAppleIconUrl($projectId),
            $this->walletAssetService->getAppleLogoUrl($projectId),
        );
    }
}
