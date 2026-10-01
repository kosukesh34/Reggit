<?php

namespace App\Http\Requests\Admin;

use App\Domain\Settings\ShopSettingKeys;
use App\Enums\MemberRegistrationMode;
use App\Enums\ProfileCustomFieldInputType;
use App\Services\Smaregi\SmaregiCustomerCustomFieldLabelSyncService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_registration_mode' => ['required', 'string', Rule::in(array_keys(MemberRegistrationMode::getValues()))],
            'smaregi_fields' => 'required|array',
            'smaregi_fields.*.enabled' => 'nullable|boolean',
            'smaregi_fields.*.required' => 'nullable|boolean',
            'custom_fields' => 'nullable|array|max:'.ShopSettingKeys::PROFILE_CUSTOM_FIELD_MAX,
            'custom_fields.*.key' => 'nullable|string|max:64|regex:/^[a-z0-9_]+$/',
            'custom_fields.*.label' => 'required|string|max:'.SmaregiCustomerCustomFieldLabelSyncService::LABEL_MAX,
            'custom_fields.*.required' => 'nullable|boolean',
            'custom_fields.*.input_type' => ['nullable', 'string', Rule::in(array_keys(ProfileCustomFieldInputType::getValues()))],
            'custom_fields.*.values' => 'nullable',
            'custom_fields.*.description' => 'nullable|string|max:255',
            'custom_fields.*.smaregi_no' => 'nullable|integer|min:1',
        ];
    }
}
