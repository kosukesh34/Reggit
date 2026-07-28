<?php

namespace App\UseCases\Admin\Settings\Data;

/** 管理画面 基本設定 表示用。不変。 */
class BasicSettingsView
{
    public function __construct(
        public array $settings,
    ) {}
}
