<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ProfileSettings;
use App\Domain\Settings\ShopSettingKeys;
use App\Enums\MemberRegistrationMode;
use App\Enums\ProfileCustomFieldInputType;
use App\Services\Smaregi\SmaregiCustomerCustomFieldLabelSyncService;
use App\UseCases\Admin\Settings\Data\UpdateProfileSettingsInput;

class UpdateProfileSettingsUseCase
{
    private ProfileSettings $shopSettingRepository;

    private SmaregiCustomerCustomFieldLabelSyncService $customFieldLabelSync;

    public function __construct(
        ProfileSettings $shopSettingRepository,
        SmaregiCustomerCustomFieldLabelSyncService $customFieldLabelSync
    ) {
        $this->shopSettingRepository = $shopSettingRepository;
        $this->customFieldLabelSync = $customFieldLabelSync;
    }

    public function handle(UpdateProfileSettingsInput $input): void
    {
        $smaregi = [];
        foreach (ShopSettingKeys::PROFILE_SMAREGI_FIELDS as $key => $label) {
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
        $usedKeys = [];
        $existingFieldNumbersByKey = $this->existingCustomFieldNumbersByKey($input->projectId);
        if ($input->rawCustomFields !== null) {
            foreach ($input->rawCustomFields as $i => $row) {
                if (count($custom) >= ShopSettingKeys::PROFILE_CUSTOM_FIELD_MAX) {
                    break;
                }
                if (! is_array($row)) {
                    continue;
                }
                $label = trim((string) ($row['label'] ?? ''));
                $key = $this->resolveCustomFieldKey($row['key'] ?? null, $label, $i, $usedKeys);
                $usedKeys[] = $key;
                $inputType = (string) ($row['input_type'] ?? ProfileCustomFieldInputType::TEXT);
                if (! array_key_exists($inputType, ProfileCustomFieldInputType::getValues())) {
                    $inputType = ProfileCustomFieldInputType::TEXT;
                }
                $values = [];
                if (ProfileCustomFieldInputType::needsValues($inputType)) {
                    $rawValues = $row['values'] ?? [];
                    if (is_string($rawValues)) {
                        $rawValues = preg_split('/\r\n|\r|\n/', $rawValues) ?: [];
                    }
                    if (is_array($rawValues)) {
                        foreach ($rawValues as $value) {
                            $value = trim((string) $value);
                            if ($value !== '') {
                                $values[] = $value;
                            }
                        }
                        $values = array_slice($values, 0, ProfileCustomFieldInputType::VALUES_MAX);
                    }
                }
                $smaregiNo = (int) ($row['smaregi_no'] ?? 0);
                if ($smaregiNo < 1) {
                    $smaregiNo = $existingFieldNumbersByKey[$key] ?? 0;
                }
                $custom[] = [
                    'key' => $key,
                    'label' => (string) ($row['label'] ?? '項目'.($i + 1)),
                    'required' => in_array($row['required'] ?? false, [1, '1', true], true),
                    'input_type' => $inputType,
                    'values' => $values,
                    'description' => trim((string) ($row['description'] ?? '')),
                    'smaregi_no' => $smaregiNo > 0 ? $smaregiNo : null,
                ];
            }
        }

        $fieldNumbersByKey = $this->customFieldLabelSync->sync($input->projectId, $custom);
        foreach ($custom as $index => $cf) {
            $key = $cf['key'];
            if (isset($fieldNumbersByKey[$key])) {
                $custom[$index]['smaregi_no'] = $fieldNumbersByKey[$key];
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

    /**
     * @param  array<int, string>  $usedKeys
     */
    private function resolveCustomFieldKey(mixed $rawKey, string $label, int $index, array $usedKeys): string
    {
        $base = preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string) $rawKey)));
        if ($base === '') {
            $base = preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace([' ', '-', '　'], '_', $label)));
        }
        if ($base === '') {
            $base = 'custom_'.($index + 1);
        }
        $key = $base;
        $suffix = 2;
        while (in_array($key, $usedKeys, true)) {
            $key = $base.'_'.$suffix;
            $suffix += 1;
        }

        return $key;
    }

    /**
     * @return array<string, int>
     */
    private function existingCustomFieldNumbersByKey(int $projectId): array
    {
        $fieldNumbersByKey = [];
        foreach ($this->shopSettingRepository->getProfileFieldSettings($projectId)['custom_fields'] ?? [] as $cf) {
            if (! is_array($cf)) {
                continue;
            }
            $key = (string) ($cf['key'] ?? '');
            $fieldNumber = (int) ($cf['smaregi_no'] ?? 0);
            if ($key === '' || $fieldNumber < 1) {
                continue;
            }
            $fieldNumbersByKey[$key] = $fieldNumber;
        }

        return $fieldNumbersByKey;
    }
}
