<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\HomeSettingsRepository;
use App\UseCases\Admin\Settings\Data\UpdateAppColorsInput;

class UpdateAdvancedSettingsUseCase
{
    public function __construct(private HomeSettingsRepository $settings) {}

    public function handle(UpdateAppColorsInput $input): void
    {
        foreach (['line_success_color' => $input->successColor, 'line_danger_color' => $input->dangerColor, 'navigation_header_background_color' => $input->headerBackground] as $key => $value) {
            if ($value !== null && $value !== '') {
                $this->settings->setValue($key, $key === 'navigation_header_background_color' ? strtoupper($value) : $value, 'string', $input->projectId);
            }
        }
        if ($input->nativeColors !== null) {
            $existing = $this->settings->getValue('native_chrome_colors', [], $input->projectId);
            $this->settings->setValue('native_chrome_colors', array_replace(is_array($existing) ? $existing : [], array_map('strtoupper', $input->nativeColors)), 'json', $input->projectId);
        }
    }
}
