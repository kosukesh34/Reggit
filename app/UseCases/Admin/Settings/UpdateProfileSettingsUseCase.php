<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use Illuminate\Http\Request;

class UpdateProfileSettingsUseCase
{
    public function handle(int $projectId, Request $request): void
    {
        $smaregi = [];
        foreach (ShopSetting::PROFILE_SMAREGI_FIELDS as $key => $label) {
            $smaregi[$key] = [
                'label' => $label,
                'enabled' => filter_var($request->input("smaregi_fields.{$key}.enabled"), FILTER_VALIDATE_BOOLEAN),
                'required' => in_array($request->input("smaregi_fields.{$key}.required"), [1, '1', true], true),
            ];
        }

        $custom = [];
        $rawCustom = $request->input('custom_fields', []);
        if (is_array($rawCustom)) {
            foreach ($rawCustom as $i => $row) {
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
        ], $projectId);
    }
}
