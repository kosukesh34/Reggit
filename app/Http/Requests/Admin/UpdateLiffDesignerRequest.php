<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLiffDesignerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'layouts_json' => 'required|string',
            'custom_css' => 'nullable|string',
            'tab_settings_json' => 'nullable|string',
        ];
    }
}
