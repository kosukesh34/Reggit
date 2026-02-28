<?php

namespace App\UseCases\Admin\Settings\Data;

/** 管理画面 Wallet 設定 表示用。不変。 */
class WalletSettingsView
{
    public function __construct(
        public ?string $googleProgramLogoUrl,
        public ?string $googleHeroImageUrl,
        public bool $appleAssetsSet
    ) {
    }
}
