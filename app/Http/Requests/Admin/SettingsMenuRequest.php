<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_items' => 'nullable|array',
            'menu_items.*.tab' => 'nullable|string|max:64',
            'menu_items.*.label' => 'nullable|string|max:255',
            'menu_items.*.icon' => 'nullable|string|max:64',
            'menu_items.*.enabled' => 'nullable|boolean',
            'menu_items.*.order' => 'nullable|integer|min:0',
        ];
    }
}
