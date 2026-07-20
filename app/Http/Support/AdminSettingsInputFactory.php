<?php

namespace App\Http\Support;

use App\UseCases\Admin\Settings\Data\UpdateProfileSettingsInput;
use App\UseCases\Admin\Settings\Data\UpdateWalletSettingsInput;
use Illuminate\Http\Request;

class AdminSettingsInputFactory
{
    public function profileSettings(int $projectId, Request $request): UpdateProfileSettingsInput
    {
        $rawCustomFields = $request->input('custom_fields');

        return new UpdateProfileSettingsInput(
            projectId: $projectId,
            rawSmaregiFields: (array) $request->input('smaregi_fields', []),
            rawCustomFields: is_array($rawCustomFields) ? $rawCustomFields : null,
            memberRegistrationMode: (string) $request->input('member_registration_mode', ''),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function walletSettings(int $projectId, Request $request, array $validated): UpdateWalletSettingsInput
    {
        return new UpdateWalletSettingsInput(
            projectId: $projectId,
            walletGoogleProgramLogoUrl: isset($validated['wallet_google_program_logo_url'])
                ? (string) $validated['wallet_google_program_logo_url']
                : null,
            walletGoogleHeroImageUrl: isset($validated['wallet_google_hero_image_url'])
                ? (string) $validated['wallet_google_hero_image_url']
                : null,
            walletAppleIcon: $request->file('wallet_apple_icon'),
            walletAppleLogo: $request->file('wallet_apple_logo'),
            walletGoogleProgramLogoFile: $request->file('wallet_google_program_logo_file'),
            walletGoogleHeroFile: $request->file('wallet_google_hero_file'),
            hasWalletGoogleProgramLogoUrlField: array_key_exists('wallet_google_program_logo_url', $validated),
            hasWalletGoogleHeroImageUrlField: array_key_exists('wallet_google_hero_image_url', $validated),
        );
    }
}
