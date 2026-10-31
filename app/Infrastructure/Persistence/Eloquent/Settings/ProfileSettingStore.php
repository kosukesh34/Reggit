<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Domain\Settings\ShopSettingKeys;
use App\Enums\MemberRegistrationMode;
use App\Enums\ProfileCustomFieldInputType;
use App\Util\AdminContext;

/** プロフィール項目と会員登録方式の設定。 */
class ProfileSettingStore
{
    /** プロフィール項目設定のデフォルト（スマレジ項目は表示ON・名前と電話は必須、任意項目は空） */
    public static function defaultProfileFieldSettings(): array
    {
        $smaregi = [];
        foreach (ShopSettingKeys::PROFILE_SMAREGI_FIELDS as $key => $label) {
            $smaregi[$key] = [
                'label' => $label,
                // 案内メールは既存会員の未設定上書きを避けるため、管理者が明示ONするまで非表示
                'enabled' => ! in_array($key, ['profile_image', 'mail_receive_flag'], true),
                'required' => in_array($key, ['name', 'phone'], true),
            ];
        }

        return [
            'smaregi_fields' => $smaregi,
            'custom_fields' => [],
        ];
    }

    public static function isSmaregiLinkedProfileField(string $key): bool
    {
        return in_array($key, ShopSettingKeys::PROFILE_SMAREGI_LINKED_KEYS, true);
    }

    /** プロフィール項目設定を取得（会員登録・編集フォームの表示とバリデーションに使用） */
    public static function getProfileFieldSettings(?int $projectId = null): array
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $raw = ShopSettingStore::getValue('profile_field_settings', null, $projectId);
        if (! is_array($raw)) {
            return self::defaultProfileFieldSettings();
        }
        $defaults = self::defaultProfileFieldSettings();
        $smaregi = $defaults['smaregi_fields'];
        foreach ($defaults['smaregi_fields'] as $key => $def) {
            if (isset($raw['smaregi_fields'][$key]) && is_array($raw['smaregi_fields'][$key])) {
                $smaregi[$key] = [
                    'label' => $raw['smaregi_fields'][$key]['label'] ?? $def['label'],
                    'enabled' => (bool) ($raw['smaregi_fields'][$key]['enabled'] ?? $def['enabled']),
                    'required' => (bool) ($raw['smaregi_fields'][$key]['required'] ?? $def['required']),
                ];
            }
            // スマレジ API 必須の氏名は非表示・任意にできない
            if ($key === 'name') {
                $smaregi[$key]['enabled'] = true;
                $smaregi[$key]['required'] = true;
            }
        }
        $custom = isset($raw['custom_fields']) && is_array($raw['custom_fields'])
            ? array_slice(array_values($raw['custom_fields']), 0, ShopSettingKeys::PROFILE_CUSTOM_FIELD_MAX)
            : [];
        $custom = array_map(static function (array $cf): array {
            $inputType = (string) ($cf['input_type'] ?? ProfileCustomFieldInputType::TEXT);
            if (! array_key_exists($inputType, ProfileCustomFieldInputType::getValues())) {
                $inputType = ProfileCustomFieldInputType::TEXT;
            }
            $values = [];
            if (isset($cf['values']) && is_array($cf['values'])) {
                foreach ($cf['values'] as $value) {
                    $value = trim((string) $value);
                    if ($value !== '') {
                        $values[] = $value;
                    }
                }
                $values = array_slice($values, 0, ProfileCustomFieldInputType::VALUES_MAX);
            }

            $smaregiNo = (int) ($cf['smaregi_no'] ?? 0);

            return [
                'key' => (string) ($cf['key'] ?? ''),
                'label' => (string) ($cf['label'] ?? ''),
                'required' => (bool) ($cf['required'] ?? false),
                'input_type' => $inputType,
                'values' => $values,
                'description' => isset($cf['description']) ? (string) $cf['description'] : '',
                'smaregi_no' => $smaregiNo > 0 ? $smaregiNo : null,
            ];
        }, array_filter($custom, static fn ($row): bool => is_array($row)));

        return [
            'smaregi_fields' => $smaregi,
            'custom_fields' => array_values($custom),
        ];
    }

    /** プロフィール項目設定を保存 */
    public static function setProfileFieldSettings(array $settings, ?int $projectId = null): void
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        ShopSettingStore::setValue('profile_field_settings', $settings, 'json', $projectId);
    }

    public static function getMemberRegistrationMode(?int $projectId = null): string
    {
        $value = (string) ShopSettingStore::getValue('member_registration_mode', MemberRegistrationMode::OPTIONAL, $projectId);
        if (array_key_exists($value, MemberRegistrationMode::getValues())) {
            return $value;
        }

        return MemberRegistrationMode::OPTIONAL;
    }

    public static function setMemberRegistrationMode(string $mode, ?int $projectId = null): void
    {
        if (! array_key_exists($mode, MemberRegistrationMode::getValues())) {
            $mode = MemberRegistrationMode::OPTIONAL;
        }
        ShopSettingStore::setValue('member_registration_mode', $mode, 'string', $projectId);
    }
}
