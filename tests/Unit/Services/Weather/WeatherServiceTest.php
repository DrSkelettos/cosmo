<?php

namespace Tests\Unit\Services\Weather;

use App\Services\Weather\WeatherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    private WeatherService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->service = new WeatherService(52.52, 13.41, 900, 'https://api.open-meteo.com/v1');
    }

    public function test_returns_current_weather_dto(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'temperature_2m' => 21.0,
                    'apparent_temperature' => 19.0,
                    'weather_code' => 2,
                    'wind_speed_10m' => 12.0,
                    'precipitation' => 0.0,
                ],
                'daily' => [
                    'precipitation_probability_max' => [10],
                ],
            ]),
        ]);

        $weather = $this->service->current();

        $this->assertNotNull($weather);
        $this->assertEquals(21.0, $weather->temperature);
        $this->assertEquals(19.0, $weather->apparentTemperature);
        $this->assertSame('Partly cloudy', $weather->condition);
        $this->assertSame(10, $weather->precipitationProbability);
        $this->assertEquals(12.0, $weather->windSpeed);
    }

    public function test_returns_today_forecast_dto(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'daily' => [
                    'temperature_2m_max' => [23.0],
                    'temperature_2m_min' => [15.0],
                    'apparent_temperature_max' => [24.0],
                    'weather_code' => [3],
                    'precipitation_probability_max' => [20],
                    'wind_speed_10m_max' => [18.0],
                ],
            ]),
        ]);

        $weather = $this->service->today();

        $this->assertNotNull($weather);
        $this->assertEquals(23.0, $weather->temperature);
        $this->assertEquals(24.0, $weather->apparentTemperature);
        $this->assertSame('Overcast', $weather->condition);
        $this->assertSame(20, $weather->precipitationProbability);
        $this->assertEquals(18.0, $weather->windSpeed);
    }

    public function test_returns_null_when_api_request_fails(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->assertNull($this->service->current());
        $this->assertNull($this->service->today());
    }

    public function test_caches_open_meteo_responses(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'temperature_2m' => 21.0,
                    'apparent_temperature' => 19.0,
                    'weather_code' => 2,
                    'wind_speed_10m' => 12.0,
                    'precipitation' => 0.0,
                ],
                'daily' => [
                    'precipitation_probability_max' => [10],
                ],
            ]),
        ]);

        $this->service->current();
        $this->service->current();

        Http::assertSentCount(1);
    }

    public function test_returns_segmented_day_forecast(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'hourly' => $this->hourlyForecast(),
            ]),
        ]);

        $segments = $this->service->forecastSegments();

        $this->assertIsArray($segments);
        $this->assertCount(3, $segments);

        $this->assertSame('Morgen', $segments[0]['label']);
        $this->assertSame(14.0, $segments[0]['minTemp']);
        $this->assertSame(19.0, $segments[0]['maxTemp']);
        $this->assertSame('Teilweise bewölkt', $segments[0]['condition']);

        $this->assertSame('Mittag', $segments[1]['label']);
        $this->assertSame('Klar', $segments[1]['condition']);

        $this->assertSame('Abend', $segments[2]['label']);
        $this->assertSame('Regen', $segments[2]['condition']);
    }

    private function hourlyForecast(): array
    {
        $times = [];
        $temperatures = [];
        $codes = [];
        $probabilities = [];
        $precipitations = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $times[] = '2026-06-24T'.str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
            $temperatures[] = 20;
            $codes[] = 2;
            $probabilities[] = 10;
            $precipitations[] = 0;
        }

        for ($hour = 6; $hour <= 11; $hour++) {
            $temperatures[$hour] = 14 + ($hour - 6);
            $codes[$hour] = 2;
            $probabilities[$hour] = 30;
            $precipitations[$hour] = 0.1;
        }

        for ($hour = 12; $hour <= 17; $hour++) {
            $temperatures[$hour] = 22 + min($hour - 12, 3);
            $codes[$hour] = 0;
            $probabilities[$hour] = 10;
            $precipitations[$hour] = 0;
        }

        for ($hour = 18; $hour <= 23; $hour++) {
            $temperatures[$hour] = 18 + min($hour - 18, 2);
            $codes[$hour] = 61;
            $probabilities[$hour] = 60;
            $precipitations[$hour] = 0.1;
        }

        return [
            'time' => $times,
            'temperature_2m' => $temperatures,
            'weather_code' => $codes,
            'precipitation_probability' => $probabilities,
            'precipitation' => $precipitations,
        ];
    }
}
