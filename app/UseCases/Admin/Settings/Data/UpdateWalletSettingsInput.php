<?php

namespace App\UseCases\Admin\Settings\Data;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class UpdateWalletSettingsInput
{
    public function __construct(
        public readonly ?UploadedFile $appleIcon,
        public readonly ?UploadedFile $appleLogo,
        public readonly ?UploadedFile $googleProgramLogoFile,
        public readonly ?UploadedFile $googleHeroFile,
        public readonly ?string $googleProgramLogoUrl,
        public readonly ?string $googleHeroImageUrl,
        public readonly bool $hasGoogleProgramLogoUrlKey,
        public readonly bool $hasGoogleHeroImageUrlKey,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromRequest(Request $request, array $validated): self
    {
        return new self(
            $request->file('wallet_apple_icon'),
            $request->file('wallet_apple_logo'),
            $request->file('wallet_google_program_logo_file'),
            $request->file('wallet_google_hero_file'),
            isset($validated['wallet_google_program_logo_url']) ? (string) $validated['wallet_google_program_logo_url'] : null,
            isset($validated['wallet_google_hero_image_url']) ? (string) $validated['wallet_google_hero_image_url'] : null,
            array_key_exists('wallet_google_program_logo_url', $validated),
            array_key_exists('wallet_google_hero_image_url', $validated),
        );
    }

    public function hasAppleFiles(): bool
    {
        return $this->appleIcon !== null || $this->appleLogo !== null;
    }
}
