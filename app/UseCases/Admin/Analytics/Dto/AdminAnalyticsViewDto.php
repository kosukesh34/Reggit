<?php

namespace App\UseCases\Admin\Analytics\Dto;

class AdminAnalyticsViewDto
{
    /** @param array<string, mixed> $summary */
    /** @param array<string, mixed> $chart */
    /** @param array<string, mixed> $breakdowns */
    public function __construct(
        public string $start,
        public string $end,
        public array $summary,
        public array $chart,
        public array $breakdowns
    ) {
    }
}
