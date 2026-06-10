<?php

namespace App\Http\Requests\Admin;

use App\Models\ShopSetting;
use Illuminate\Foundation\Http\FormRequest;

class SettingsFeaturesRequest extends FormRequest
{
    private const HEX_OR_EMPTY = 'regex:/^$|^#[0-9A-Fa-f]{6}$/';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'features' => 'nullable|array',
            'admin_sidebar_color' => ['nullable', 'string', 'max:32', self::HEX_OR_EMPTY],
            'admin_header_color' => ['nullable', 'string', 'max:32', self::HEX_OR_EMPTY],
            'admin_primary_color' => ['nullable', 'string', 'max:32', self::HEX_OR_EMPTY],
            'stamp_default_image_url' => 'nullable|string|max:2048|url',
            'coupon_default_image_url' => 'nullable|string|max:2048|url',
            'frontend_logo_url' => 'nullable|string|max:2048|url',
            'frontend_header_text' => 'nullable|string|max:255',
        ];

        foreach (array_keys(ShopSetting::FEATURE_FLAGS) as $key) {
            $rules['features.'.$key] = 'nullable|in:0,1';
        }

        return $rules;
    }
}
