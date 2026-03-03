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
        ];
    }
}
