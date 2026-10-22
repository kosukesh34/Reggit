<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class UpdateFeatureSettingsInput
{
    /** @param array<string, bool> $features */
    public function __construct(
        public int $projectId,
        public array $features,
        public ?bool $usePoints = null,
        public ?string $pointUsageScope = null,
        public ?bool $useTransactionStamps = null,
    ) {}
}
