<?php

namespace App\UseCases\Admin\Settings\Data;

use Illuminate\Http\UploadedFile;

final class UpdateWalletSettingsInput
{
    public function __construct(
        public readonly int $projectId,
        public readonly ?string $walletGoogleProgramLogoUrl,
        public readonly ?string $walletGoogleHeroImageUrl,
        public readonly ?UploadedFile $walletAppleIcon,
        public readonly ?UploadedFile $walletAppleLogo,
        public readonly ?UploadedFile $walletGoogleProgramLogoFile,
        public readonly ?UploadedFile $walletGoogleHeroFile,
        public readonly bool $hasWalletGoogleProgramLogoUrlField,
        public readonly bool $hasWalletGoogleHeroImageUrlField,
    ) {}
}
