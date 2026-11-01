<?php

namespace App\Domain\Weather\Policies;

use App\Domain\Weather\ValueObjects\HourlyForecast;
use Carbon\CarbonImmutable;

final class RainyPeriodPolicy
{
    /**
     * $from〜$to に、降水確率が基準以上の時間が $minHours 時間以上続くか。
     * 一時的な雨（にわか雨）で配らないよう、連続した時間だけを数える。
     *
     * @param  list<HourlyForecast>  $forecasts
     */
    public function isRainy(array $forecasts, CarbonImmutable $from, CarbonImmutable $to, int $minProbability, int $minHours): bool
    {
        $consecutive = 0;
        $previous = null;
        foreach ($forecasts as $forecast) {
            if ($forecast->at >= $to || $forecast->at->addHour() <= $from) {
                continue;
            }
            $continues = $previous !== null && $previous->addHour()->equalTo($forecast->at);
            $consecutive = $forecast->precipitationProbability >= $minProbability
                ? ($continues ? $consecutive + 1 : 1)
                : 0;
            if ($consecutive >= $minHours) {
                return true;
            }
            $previous = $forecast->at;
        }

        return false;
    }
}
