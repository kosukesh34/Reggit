<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class UpdateAppColorsInput
{
    /** @param array<string, string>|null $nativeColors */
    public function __construct(
        public int $projectId,
        public ?string $successColor,
        public ?string $dangerColor,
        public ?string $headerBackground,
        public ?array $nativeColors,
    ) {}
}
