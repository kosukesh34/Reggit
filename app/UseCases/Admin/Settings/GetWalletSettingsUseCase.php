<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Services\Admin\WalletAssetService;
use App\UseCases\Admin\Settings\Data\WalletSettingsView;

class GetWalletSettingsUseCase
{
    public function __construct(
        private WalletAssetService $walletAssetService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): WalletSettingsView
    {
        $googleProgramLogoUrl = $this->shopSettingRepository->getWalletGoogleProgramLogoUrl($projectId);
        $googleHeroImageUrl = $this->shopSettingRepository->getWalletGoogleHeroImageUrl($projectId);
        $appleAssetsSet = $this->walletAssetService->hasAppleAssets($projectId);

        return new WalletSettingsView($googleProgramLogoUrl, $googleHeroImageUrl, $appleAssetsSet);
    }
}
