<?php

namespace App\Http\Requests\Admin;

use App\Domain\Settings\Repositories\ProfileSettings;
use App\Enums\MemberNotificationPreference;
use App\Enums\ProfileCustomFieldInputType;
use App\Models\Member;
use App\Models\Scopes\ProjectScope;
use App\Rules\ProfileValidationRules;
use App\Support\PersonNameParts;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MemberUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name_last') || $this->has('name_first')) {
            $this->merge([
                'name' => PersonNameParts::join(
                    $this->input('name_last'),
                    $this->input('name_first')
                ),
            ]);
        }
        if ($this->has('kana_last') || $this->has('kana_first')) {
            $this->merge([
                'kana' => PersonNameParts::join(
                    $this->input('kana_last'),
                    $this->input('kana_first')
                ),
            ]);
        }
    }

    public function rules(): array
    {
        $memberId = (int) $this->route('id');
        $format = ProfileValidationRules::formatRulesByField();
        $rules = [
            'name_last' => ['nullable', 'string', 'max:'.ProfileValidationRules::NAME_PART_MAX],
            'name_first' => ['nullable', 'string', 'max:'.ProfileValidationRules::NAME_PART_MAX],
            'kana_last' => ['nullable', 'string', 'max:'.ProfileValidationRules::KANA_PART_MAX, 'regex:'.ProfileValidationRules::KANA_REGEX],
            'kana_first' => ['nullable', 'string', 'max:'.ProfileValidationRules::KANA_PART_MAX, 'regex:'.ProfileValidationRules::KANA_REGEX],
            'name' => array_merge(['nullable'], $format['name']),
            'kana' => array_merge(['nullable'], $format['kana']),
            'email' => array_merge(['nullable'], $format['email']),
            'phone' => array_merge(['nullable'], $format['phone']),

            'member_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('members', 'member_number')->ignore($memberId),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'rank' => ['nullable', 'string', 'max:64'],
            'birthday' => array_merge(['nullable'], $format['birthday']),
            'address' => array_merge(['nullable'], $format['address']),
            'postal_code' => array_merge(['nullable'], $format['postal_code']),
            'sex' => array_merge(['nullable'], $format['sex']),
            'customer_store_id' => ['nullable', 'integer', Rule::exists('stores', 'id')->where('project_id', AdminContext::requireCurrentProjectId())->where('is_active', true)],
            'mail_receive_flag' => array_merge(['nullable'], $format['mail_receive_flag']),
            'notification_preference' => ['required', Rule::in(MemberNotificationPreference::keys())],

            'point_set' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'point_add' => ['nullable', 'integer', 'min:0'],
            'point_subtract' => ['nullable', 'integer', 'min:0'],
            'point_description' => ['nullable', 'string', 'max:255'],

            'profile_extra' => ['nullable', 'string', 'max:2000'],
            'custom_profile' => ['nullable', 'array'],
            'custom_profile_form' => ['sometimes', 'boolean'],
        ];

        foreach ($this->customProfileFieldDefinitions() as $cf) {
            $key = (string) ($cf['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $inputType = (string) ($cf['input_type'] ?? ProfileCustomFieldInputType::TEXT);
            $values = isset($cf['values']) && is_array($cf['values']) ? array_map('strval', $cf['values']) : [];
            if ($inputType === ProfileCustomFieldInputType::CHECKBOX) {
                $rules['custom_profile.'.$key] = ['nullable', 'array'];
                if ($values !== []) {
                    $rules['custom_profile.'.$key.'.*'] = ['string', Rule::in($values)];
                }

                continue;
            }
            if (ProfileCustomFieldInputType::needsValues($inputType) && $values !== []) {
                $rules['custom_profile.'.$key] = ['nullable', 'string', Rule::in($values)];

                continue;
            }
            $rules['custom_profile.'.$key] = ['nullable', 'string', 'max:'.ProfileValidationRules::CUSTOM_TEXT_MAX];
        }

        return $rules;
    }

    public function messages(): array
    {
        return array_merge(ProfileValidationRules::messages(), [
            'member_number.required' => '会員番号を入力してください',
            'member_number.unique' => 'この会員番号は既に使用されています',
            'status.in' => 'ステータスを選択してください',
            'notification_preference.required' => 'お知らせ通知を選択してください',
            'notification_preference.in' => 'お知らせ通知を選択してください',
            'kana_last.regex' => 'フリガナ（姓）はカタカナで入力してください',
            'kana_first.regex' => 'フリガナ（名）はカタカナで入力してください',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customProfileFieldDefinitions(): array
    {
        $member = Member::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->find((int) $this->route('id'));
        if ($member === null) {
            return [];
        }
        $settings = resolve(ProfileSettings::class)->getProfileFieldSettings((int) $member->project_id);

        return array_values($settings['custom_fields'] ?? []);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('point_set')) {
                return;
            }
            $add = (int) $this->input('point_add', 0);
            $sub = (int) $this->input('point_subtract', 0);
            if ($add > 0 && $sub > 0) {
                $validator->errors()->add('point_add', 'ポイント加算と減算は同時に指定できません');
                $validator->errors()->add('point_subtract', 'ポイント加算と減算は同時に指定できません');
            }
        });
    }

    /**
     * ポイント操作を取得。直接指定 > 加算 > 減算 の優先順。
     */
    public function getPointAdjustment(): ?array
    {
        if ($this->filled('point_set')) {
            return [
                'type' => 'set',
                'points' => max(0, (int) $this->input('point_set')),
                'description' => $this->input('point_description'),
            ];
        }

        $add = (int) $this->input('point_add', 0);
        $sub = (int) $this->input('point_subtract', 0);
        if ($add > 0) {
            return ['type' => 'add', 'points' => $add, 'description' => $this->input('point_description')];
        }
        if ($sub > 0) {
            return ['type' => 'subtract', 'points' => $sub, 'description' => $this->input('point_description')];
        }

        return null;
    }

    /**
     * profile_extra を配列で取得（カスタム項目フォーム優先、なければ JSON 文字列をデコード）
     *
     * @return array<string, mixed>|null
     */
    public function getProfileExtraArray(): ?array
    {
        $definitions = $this->customProfileFieldDefinitions();
        if ($definitions === []) {
            $raw = $this->input('profile_extra');
            if ($raw === null || $raw === '') {
                return null;
            }
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : null;
        }

        $extra = [];
        $customInput = $this->input('custom_profile', []);
        if (! is_array($customInput)) {
            $customInput = [];
        }
        foreach ($definitions as $cf) {
            $key = (string) ($cf['key'] ?? '');
            if ($key === '') {
                continue;
            }
            if (! array_key_exists($key, $customInput) && ! $this->boolean('custom_profile_form')) {
                continue;
            }
            $inputType = (string) ($cf['input_type'] ?? ProfileCustomFieldInputType::TEXT);
            if ($inputType === ProfileCustomFieldInputType::CHECKBOX) {
                $values = $customInput[$key] ?? [];
                $extra[$key] = is_array($values) ? array_values($values) : [];

                continue;
            }
            if (! array_key_exists($key, $customInput)) {
                $extra[$key] = null;

                continue;
            }
            $value = $customInput[$key];
            $extra[$key] = is_string($value) && $value === '' ? null : $value;
        }

        return $extra === [] ? null : $extra;
    }
}
