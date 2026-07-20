<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\MemberRegistrationMode;
use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\UpdateProfileSettingsInput;

class UpdateProfileSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(UpdateProfileSettingsInput $input): void
    {
        $smaregi = [];
        foreach (ShopSetting::PROFILE_SMAREGI_FIELDS as $key => $label) {
            $row = $input->rawSmaregiFields[$key] ?? [];
            $enabled = filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $required = in_array($row['required'] ?? false, [1, '1', true], true);
            if ($key === 'name') {
                $enabled = true;
                $required = true;
            }
            $smaregi[$key] = [
                'label' => $label,
                'enabled' => $enabled,
                'required' => $required,
            ];
        }

        $custom = [];
        if ($input->rawCustomFields !== null) {
            foreach ($input->rawCustomFields as $i => $row) {
                if (count($custom) >= ShopSetting::PROFILE_CUSTOM_FIELD_MAX) {
                    break;
                }
                $key = $row['key'] ?? ('custom_'.($i + 1));
                $key = preg_replace('/[^a-z0-9_]/', '', $key) ?: ('custom_'.($i + 1));
                $custom[] = [
                    'key' => $key,
                    'label' => $row['label'] ?? '項目'.($i + 1),
                    'required' => (bool) ($row['required'] ?? false),
                ];
            }
        }

        $this->shopSettingRepository->setProfileFieldSettings([
            'smaregi_fields' => $smaregi,
            'custom_fields' => $custom,
        ], $input->projectId);

        $mode = $input->memberRegistrationMode;
        if (! array_key_exists($mode, MemberRegistrationMode::getValues())) {
            $mode = MemberRegistrationMode::OPTIONAL;
        }
        $this->shopSettingRepository->setMemberRegistrationMode($mode, $input->projectId);
    }
}
