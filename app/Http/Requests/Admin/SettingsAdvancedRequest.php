<?php

namespace App\Http\Requests\Admin;

use App\UseCases\Admin\Settings\Data\UpdateAppColorsInput;
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
            'navigation_header_background_color' => ['nullable', 'string', self::HEX_REGEX],
            'native_chrome_colors' => 'nullable|array:header_icon,footer_background,footer_icon,footer_active_icon,footer_active_background,primary_button_background,primary_button_text,secondary_button_background,secondary_button_text',
            'native_chrome_colors.*' => ['required', 'string', self::HEX_REGEX],
        ];
    }

    public function toInput(int $projectId): UpdateAppColorsInput
    {
        $v = $this->validated();

        return new UpdateAppColorsInput($projectId, $v['line_success_color'] ?? null, $v['line_danger_color'] ?? null, $v['navigation_header_background_color'] ?? null, $v['native_chrome_colors'] ?? null);
    }
}
