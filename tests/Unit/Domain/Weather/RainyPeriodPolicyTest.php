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
            fn (int $hour, int $probability) => new HourlyForecast(CarbonImmutable::parse('2026-11-02')->setTime($hour, 0), $probability, 0.0),
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

    public function test_判定開始時刻を含む1時間は数えること(): void
    {
        $this->assertTrue($this->isRainy([13 => 90, 14 => 90], '13:30'));
        $this->assertFalse($this->isRainy([13 => 90, 14 => 90], '14:00'));
    }
}
