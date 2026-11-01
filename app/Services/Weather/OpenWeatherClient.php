<?php

namespace App\Services\Weather;

use App\Domain\Weather\ValueObjects\HourlyForecast;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** OpenWeatherMap One Call 3.0（One Call by Call プランの契約が必要） */
class OpenWeatherClient
{
    private const URL = 'https://api.openweathermap.org/data/3.0/onecall';

    /** @return list<HourlyForecast> 48時間分。約1km以内の地点は同じ結果を30分使い回す */
    public function hourly(float $latitude, float $longitude): array
    {
        $key = trim((string) config('services.openweather.key'));
        if ($key === '') {
            throw new \RuntimeException('OPENWEATHER_API_KEY が設定されていません');
        }
        $lat = round($latitude, 2);
        $lon = round($longitude, 2);
        $hours = Cache::remember("weather:hourly:{$lat},{$lon}", now()->addMinutes(30), fn (): array => Http::timeout(10)
            ->retry(2, 500)
            ->get(self::URL, ['lat' => $lat, 'lon' => $lon, 'exclude' => 'current,minutely,daily,alerts', 'appid' => $key])
            ->throw()
            ->json('hourly', []));

        // 雨が降らない時間は rain 自体が返らない
        return array_map(static fn (array $hour) => new HourlyForecast(
            CarbonImmutable::createFromTimestamp((int) $hour['dt'], config('app.timezone')),
            (int) round(((float) ($hour['pop'] ?? 0)) * 100),
            (float) ($hour['rain']['1h'] ?? 0),
        ), array_values($hours));
    }
}
