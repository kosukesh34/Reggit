<?php

namespace App\UseCases\Admin\Settings\Dto;

class RankSettingsViewDto
{
    /** @param array<int, array<string, mixed>> $rankSettings */
    public function __construct(
        public array $rankSettings,
        public string $rankBasis,
        public int $pointEarnRate,
        public int $pointsValidDays
    ) {
    }
}
