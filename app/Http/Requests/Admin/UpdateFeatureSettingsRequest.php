<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeatureSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'frontend_logo' => 'nullable|image|max:2048',
            'frontend_logo_clear' => 'nullable|in:0,1',
            'frontend_header_text' => 'nullable|string|max:255',
            'stamp_default_image_url' => 'nullable|string|max:2048',
            'admin_sidebar_color' => 'nullable|string|max:32',
            'admin_header_color' => 'nullable|string|max:32',
            'admin_primary_color' => 'nullable|string|max:32',
            'features' => 'nullable|array',
        ];
    }
}
