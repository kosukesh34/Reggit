<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class UpdateRankSettingsInput
{
    /** @param list<array<string, mixed>> $ranks */
    public function __construct(
        public int $projectId,
        public string $rankBasis,
        public string $aggregationType,
        public int $periodDays,
        public bool $rankDownEnabled,
        public int $pointEarnRate,
        public int $pointsValidDays,
        public int $pointsPerOrder,
        public int $stampEarnPerAmount,
        public array $ranks,
    ) {}
}
