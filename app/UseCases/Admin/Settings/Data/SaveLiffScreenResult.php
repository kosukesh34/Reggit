<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * SaveLiffScreenUseCase の実行結果。不変。
 */
class SaveLiffScreenResult
{
    public function __construct(
        public readonly bool $saved,
        public readonly int $savedScreensCount
    ) {
    }
}
