<?php

namespace App\Services;

use App\Domain\Settings\Repositories\MemberSettings;
use App\Enums\MemberNotificationPreference;
use App\Enums\ProfileCustomFieldInputType;
use App\Rules\ProfileValidationRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/** Builds profile validation rules from project settings. */
final class MemberProfileRules
{
    public function __construct(
        private MemberSettings $settings,
        private CustomerStoreProfile $customerStoreProfile,
    ) {}

    public function getProfileValidationRules(int $projectId): array
    {
        $settings = $this->settings->getProfileFieldSettings($projectId);
        $rules = [];
        $formatRules = ProfileValidationRules::formatRulesByField();
        foreach ($settings['smaregi_fields'] ?? [] as $key => $field) {
            if ((bool) ($field['enabled'] ?? false) === false) {
                continue;
            }
            if ($key === 'profile_image') {
                continue;
            }
            $rule = [(bool) ($field['required'] ?? false) === true ? 'required' : 'nullable'];
            foreach ($formatRules[$key] ?? ['string', 'max:'.ProfileValidationRules::CUSTOM_TEXT_MAX] as $formatRule) {
                $rule[] = $formatRule;
            }
            if ($key === 'customer_store_id') {
                $rule[] = $this->customerStoreProfile->rule($projectId);
            }
            $rules[$key] = $rule;
        }
        foreach ($settings['custom_fields'] ?? [] as $cf) {
            $key = $cf['key'] ?? null;
            if ($key === null || $key === '') {
                continue;
            }
            foreach ($this->buildCustomFieldValidationRules((string) $key, $cf) as $ruleKey => $rule) {
                $rules[$ruleKey] = $rule;
            }
        }

        if (isset($rules['name']) && is_array($rules['name'])) {
            $rules['name'] = array_map(
                static fn (string $rule): string => $rule === 'required' ? 'required_without:line_name' : $rule,
                $rules['name'],
            );
        }
        // ご利用店舗は項目設定で無効でも、店舗が複数あれば受け付ける（MemberDataReader の入力欄と対応）
        if (! isset($rules['customer_store_id']) && count($this->customerStoreProfile->options($projectId)) > 1) {
            $rules['customer_store_id'] = ['nullable', 'integer', $this->customerStoreProfile->rule($projectId)];
        }
        $rules['line_name'] = ['nullable', 'string', 'max:'.ProfileValidationRules::NAME_MAX];

        $rules['notification_preference'] = ['nullable', Rule::in(MemberNotificationPreference::keys())];

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $cf
     * @return array<string, list<string|In>>
     */
    private function buildCustomFieldValidationRules(string $key, array $cf): array
    {
        $required = (bool) ($cf['required'] ?? false) === true;
        $inputType = (string) ($cf['input_type'] ?? ProfileCustomFieldInputType::TEXT);
        $base = [$required ? 'required' : 'nullable'];
        $values = isset($cf['values']) && is_array($cf['values'])
            ? array_values(array_filter(array_map('strval', $cf['values']), static fn (string $v): bool => $v !== ''))
            : [];

        if ($inputType === ProfileCustomFieldInputType::CHECKBOX) {
            $rules = [
                $key => array_merge($base, ['array']),
            ];
            if ($values !== []) {
                $rules[$key.'.*'] = ['string', Rule::in($values)];
            }

            return $rules;
        }

        if ($inputType === ProfileCustomFieldInputType::TEXTAREA) {
            return [$key => array_merge($base, ['string', 'max:'.ProfileValidationRules::CUSTOM_TEXT_MAX])];
        }

        if (ProfileCustomFieldInputType::needsValues($inputType) && $values !== []) {
            return [$key => array_merge($base, ['string', Rule::in($values)])];
        }

        return [$key => array_merge($base, ['string', 'max:'.ProfileValidationRules::CUSTOM_TEXT_MAX])];
    }
}
