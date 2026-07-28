<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsAdvancedRequest extends FormRequest
{
    private const HEX_REGEX = 'regex:/^#[0-9A-Fa-f]{6}$/';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'line_success_color' => ['nullable', 'string', self::HEX_REGEX],
            'line_danger_color' => ['nullable', 'string', self::HEX_REGEX],
        ];
    }
}
