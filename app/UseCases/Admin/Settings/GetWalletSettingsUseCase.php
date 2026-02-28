<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Services\Admin\WalletAssetService;
use App\UseCases\Admin\Settings\Data\WalletSettingsView;

class GetWalletSettingsUseCase
{
    private WalletAssetService $walletAssetService;

    public function __construct(WalletAssetService $walletAssetService)
    {
        $this->walletAssetService = $walletAssetService;
    }

    public function handle(int $projectId): WalletSettingsView
    {
        $googleProgramLogoUrl = ShopSetting::getWalletGoogleProgramLogoUrl($projectId);
        $googleHeroImageUrl = ShopSetting::getWalletGoogleHeroImageUrl($projectId);
        $appleAssetsSet = $this->walletAssetService->hasAppleAssets($projectId);

        return new WalletSettingsView($googleProgramLogoUrl, $googleHeroImageUrl, $appleAssetsSet);
    }
}
