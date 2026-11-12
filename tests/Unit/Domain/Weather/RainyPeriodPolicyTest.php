<?php

namespace Tests\Unit\Domain\Weather;

use App\Domain\Weather\Policies\RainyPeriodPolicy;
use App\Domain\Weather\ValueObjects\HourlyForecast;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class RainyPeriodPolicyTest extends TestCase
{
    /**
     * @param  array<int, int>  $probabilities  時 => 降水確率
     * @return list<HourlyForecast>
     */
    private function forecasts(array $probabilities): array
    {
        return array_map(
            fn (int $hour, int $probability) => new HourlyForecast(CarbonImmutable::parse('2026-11-02')->setTime($hour, 0), $probability, 0.0, 20.0),
            array_keys($probabilities),
            array_values($probabilities),
        );
    }

    private function isRainy(array $probabilities, string $from = '10:00', string $to = '22:00', int $minHours = 2): bool
    {
        $day = CarbonImmutable::parse('2026-11-02');

        return (new RainyPeriodPolicy)->isRainy(
            $this->forecasts($probabilities),
            $day->setTimeFromTimeString($from),
            $day->setTimeFromTimeString($to),
            50,
            $minHours,
        );
    }

    public function test_雨量の基準があれば雨量も基準以上の時間だけ数えること(): void
    {
        $day = CarbonImmutable::parse('2026-11-02');
        $forecasts = [
            new HourlyForecast($day->setTime(12, 0), 80, 2.0, 20.0),
            new HourlyForecast($day->setTime(13, 0), 80, 0.5, 20.0),
            new HourlyForecast($day->setTime(14, 0), 80, 1.0, 20.0),
        ];
        $policy = new RainyPeriodPolicy;
        [$from, $to] = [$day->setTime(10, 0), $day->setTime(22, 0)];

        $this->assertTrue($policy->isRainy($forecasts, $from, $to, 50, 2));
        // 13時は 0.5mm で 1mm に届かないため、雨が途切れる
        $this->assertFalse($policy->isRainy($forecasts, $from, $to, 50, 2, 1.0));
        $this->assertTrue($policy->isRainy($forecasts, $from, $to, 50, 2, 0.5));
    }

    public function test_基準以上が指定時間続けば雨と判定すること(): void
    {
        $this->assertTrue($this->isRainy([12 => 60, 13 => 50]));
    }

    public function test_途切れた雨は続いた時間に数えないこと(): void
    {
        $this->assertFalse($this->isRainy([12 => 80, 13 => 40, 14 => 80]));
        $this->assertFalse($this->isRainy([12 => 80, 14 => 80]));
    }

    public function test_営業時間外の雨は数えないこと(): void
    {
        $this->assertFalse($this->isRainy([8 => 100, 9 => 100, 10 => 100, 22 => 100, 23 => 100]));
    }

    public function test_判定範囲に入る分だけを数えること(): void
    {
        $this->assertFalse($this->isRainy([13 => 90, 14 => 90], '13:30'));
        $this->assertTrue($this->isRainy([13 => 90, 14 => 90, 15 => 90], '13:30'));
        $this->assertFalse($this->isRainy([13 => 90, 14 => 90, 15 => 90], '13:30', '15:15'));
    }
}
