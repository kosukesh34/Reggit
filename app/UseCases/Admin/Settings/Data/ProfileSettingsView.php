<?php

namespace App\UseCases\Admin\Settings\Data;

/** 管理画面 プロフィール設定 表示用。 */
class ProfileSettingsView
{
    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $memberRegistrationModeOptions
     * @param  array<string, string>  $customFieldInputTypeOptions
     */
    public function __construct(
        public array $settings,
        public array $memberRegistrationModeOptions,
        public array $customFieldInputTypeOptions,
    ) {}
}
