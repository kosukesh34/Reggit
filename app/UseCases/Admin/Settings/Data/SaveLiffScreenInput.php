<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * 保存した画面の保存ユースケースへの入力。検証済み。不変。
 */
class SaveLiffScreenInput
{
    public function __construct(
        public readonly string $name,
        public readonly string $layoutsJson,
        public readonly string $tabSettingsJson
    ) {}
}
