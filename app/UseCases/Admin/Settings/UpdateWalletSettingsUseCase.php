<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Services\Admin\WalletAssetService;
use Illuminate\Http\Request;

/**
 * Wallet 画像設定の更新。
 * 戻り値の messages はリダイレクト時の success メッセージ用。
 *
 * @return array<int, string>
 */
class UpdateWalletSettingsUseCase
{
    private WalletAssetService $walletAssetService;

    public function __construct(WalletAssetService $walletAssetService)
    {
        $this->walletAssetService = $walletAssetService;
    }

    /**
     * @param array<string, mixed> $validated
     *
     * @return array<int, string>
     */
    public function handle(int $projectId, Request $request, array $validated): array
    {
        $messages = [];

        if ($request->hasFile('wallet_apple_icon') || $request->hasFile('wallet_apple_logo')) {
            $saved = $this->walletAssetService->saveAppleAssets($projectId, $request);
            if ($saved) {
                $messages[] = 'Apple Wallet の画像を更新しました。';
            }
        }

        if ($request->hasFile('wallet_google_program_logo_file')) {
            $file = $request->file('wallet_google_program_logo_file');
            if ($file !== null) {
                $url = $this->walletAssetService->saveGoogleAsset($projectId, $file, 'program_logo');
                if ($url !== null) {
                    ShopSetting::setWalletGoogleProgramLogoUrl($url, $projectId);
                    $messages[] = 'Google Wallet プログラムロゴを更新しました。';
                }
            }
        }
        if ($request->hasFile('wallet_google_hero_file')) {
            $file = $request->file('wallet_google_hero_file');
            if ($file !== null) {
                $url = $this->walletAssetService->saveGoogleAsset($projectId, $file, 'hero');
                if ($url !== null) {
                    ShopSetting::setWalletGoogleHeroImageUrl($url, $projectId);
                    $messages[] = 'Google Wallet ヒーロー画像を更新しました。';
                }
            }
        }

        if (! $request->hasFile('wallet_google_program_logo_file') && array_key_exists('wallet_google_program_logo_url', $validated)) {
            ShopSetting::setWalletGoogleProgramLogoUrl($validated['wallet_google_program_logo_url'] ?: null, $projectId);
        }
        if (! $request->hasFile('wallet_google_hero_file') && array_key_exists('wallet_google_hero_image_url', $validated)) {
            ShopSetting::setWalletGoogleHeroImageUrl($validated['wallet_google_hero_image_url'] ?: null, $projectId);
        }

        return $messages;
    }
}
