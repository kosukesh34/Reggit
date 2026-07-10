<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\UpdateProfileSettingsInput;

class UpdateProfileSettingsUseCase
{
    public function handle(UpdateProfileSettingsInput $input): void
    {
        $smaregi = [];
        foreach (ShopSetting::PROFILE_SMAREGI_FIELDS as $key => $label) {
            $row = $input->rawSmaregiFields[$key] ?? [];
            $smaregi[$key] = [
                'label' => $label,
                'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'required' => in_array($row['required'] ?? false, [1, '1', true], true),
            ];
        }

        $custom = [];
        if ($input->rawCustomFields !== null) {
            foreach ($input->rawCustomFields as $i => $row) {
                $key = $row['key'] ?? ('custom_'.($i + 1));
                $key = preg_replace('/[^a-z0-9_]/', '', $key) ?: ('custom_'.($i + 1));
                $custom[] = [
                    'key' => $key,
                    'label' => $row['label'] ?? '項目'.($i + 1),
                    'required' => (bool) ($row['required'] ?? false),
                ];
            }
        }

        ShopSetting::setProfileFieldSettings([
            'smaregi_fields' => $smaregi,
            'custom_fields' => $custom,
        ], $input->projectId);
    }
}
