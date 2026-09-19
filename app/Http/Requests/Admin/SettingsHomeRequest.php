<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsHomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tabs' => 'nullable|array',
            'tabs.*.tab' => 'nullable|string|max:64',
            'tabs.*.label' => 'nullable|string|max:255',
            'tabs.*.icon' => 'nullable|string|max:64',
            'tabs.*.order' => 'nullable|integer|min:0',
            'tabs.*.enabled' => 'nullable|boolean',
            'navigation_header_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'navigation_header_image_clear' => 'nullable|in:0,1',
            'navigation_header_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'home_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
            'home_image_clear' => 'nullable|in:0,1',
            'home_menu_layout' => 'required|in:list,grid_3x2,grid_2_columns',
        ];
    }
}
