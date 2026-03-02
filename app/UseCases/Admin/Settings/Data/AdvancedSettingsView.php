<?php

namespace App\UseCases\Admin\Settings\Data;

/** 管理画面 配色設定 表示用。不変。 */
class AdvancedSettingsView
{
    public function __construct(
        public array $settings
    ) {}
}
