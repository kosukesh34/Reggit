<?php

namespace App\UseCases\Admin\Settings\Data;

use App\Models\ShopSetting;

class UpdateProfileSettingsInput
{
    /**
     * @param  array<string, array{label: string, enabled: bool, required: bool}>  $smaregiFields
     * @param  array<int, array{key: string, label: string, required: bool}>  $customFields
     */
    public function __construct(
        public readonly array $smaregiFields,
        public readonly array $customFields,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        $smaregi = [];
        $rawSmaregi = is_array($validated['smaregi_fields'] ?? null) ? $validated['smaregi_fields'] : [];
        foreach (ShopSetting::PROFILE_SMAREGI_FIELDS as $key => $label) {
            $row = is_array($rawSmaregi[$key] ?? null) ? $rawSmaregi[$key] : [];
            $smaregi[$key] = [
                'label' => $label,
                'enabled' => (bool) ($row['enabled'] ?? false),
                'required' => (bool) ($row['required'] ?? false),
            ];
        }

        $custom = [];
        $rawCustom = $validated['custom_fields'] ?? [];
        if (is_array($rawCustom)) {
            foreach ($rawCustom as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $key = $row['key'] ?? ('custom_'.($i + 1));
                $key = preg_replace('/[^a-z0-9_]/', '', (string) $key) ?: ('custom_'.($i + 1));
                $custom[] = [
                    'key' => $key,
                    'label' => $row['label'] ?? '項目'.($i + 1),
                    'required' => (bool) ($row['required'] ?? false),
                ];
            }
        }

        return new self($smaregi, $custom);
    }
}
