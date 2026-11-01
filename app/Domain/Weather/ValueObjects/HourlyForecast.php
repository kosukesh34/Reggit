<?php

namespace App\Domain\Weather\ValueObjects;

use Carbon\CarbonImmutable;

/** 1時間ごとの予報。$at はその1時間の開始時刻 */
final class HourlyForecast
{
    public function __construct(
        public readonly CarbonImmutable $at,
        public readonly int $precipitationProbability,
        public readonly float $rainMm,
    ) {}
}
