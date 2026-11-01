<?php

namespace Tests\Unit\Services\Weather;

use App\Services\Weather\OpenWeatherClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenWeatherClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openweather.key' => 'test-key', 'cache.default' => 'array']);
        Cache::flush();
    }

    public function test_1時間ごとの予報を降水確率と雨量に変換すること(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response(['hourly' => [
            ['dt' => 1793595600, 'pop' => 0.56, 'rain' => ['1h' => 0.45]],
            ['dt' => 1793599200, 'pop' => 0],
        ]])]);

        $forecasts = app(OpenWeatherClient::class)->hourly(33.54940322163531, 130.4241976266792);

        $this->assertSame(
            [['2026-11-02 14:00', 56, 0.45], ['2026-11-02 15:00', 0, 0.0]],
            array_map(fn ($f) => [$f->at->format('Y-m-d H:i'), $f->precipitationProbability, $f->rainMm], $forecasts),
        );
        Http::assertSent(fn (Request $request) => $request['lat'] === 33.55 && $request['lon'] === 130.42 && $request['appid'] === 'test-key');
    }

    public function test_近い地点は30分以内なら再取得しないこと(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response(['hourly' => []])]);

        app(OpenWeatherClient::class)->hourly(33.5494, 130.4242);
        app(OpenWeatherClient::class)->hourly(33.5491, 130.4239);

        Http::assertSentCount(1);
    }

    public function test_キーが未設定なら問い合わせずに失敗すること(): void
    {
        config(['services.openweather.key' => '']);
        Http::fake();

        $this->expectException(\RuntimeException::class);
        try {
            app(OpenWeatherClient::class)->hourly(33.5, 130.4);
        } finally {
            Http::assertNothingSent();
        }
    }
}
