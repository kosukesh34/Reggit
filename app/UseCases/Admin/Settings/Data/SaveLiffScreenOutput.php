<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * SaveLiffScreenUseCase の出力。不変。
 */
class SaveLiffScreenOutput
{
    public function __construct(
        public readonly bool $saved,
        public readonly int $savedScreensCount
    ) {}
}
