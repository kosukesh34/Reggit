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
            walletAppleIcon: $request->file('wallet_apple_icon'),
            walletAppleLogo: $request->file('wallet_apple_logo'),
            walletGoogleProgramLogoFile: $request->file('wallet_google_program_logo_file'),
            walletGoogleHeroFile: $request->file('wallet_google_hero_file'),
            clearAppleIcon: ($validated['wallet_apple_icon_clear'] ?? '0') === '1',
            clearAppleLogo: ($validated['wallet_apple_logo_clear'] ?? '0') === '1',
            clearGoogleProgramLogo: ($validated['wallet_google_program_logo_clear'] ?? '0') === '1',
            clearGoogleHero: ($validated['wallet_google_hero_clear'] ?? '0') === '1',
        );
    }
}
