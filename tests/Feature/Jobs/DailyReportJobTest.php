<?php

namespace Tests\Feature\Jobs;

use App\Jobs\DailyReportJob;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DailyReportJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telegram.bot_token' => 'test-token',
            'telegram.owner_id' => 123456,
            'telegram.parse_mode' => 'MarkdownV2',
            'weather.latitude' => 52.52,
            'weather.longitude' => 13.41,
            'weather.cache_ttl' => 900,
            'weather.base_url' => 'https://api.open-meteo.com/v1',
        ]);
    }

    public function test_sends_daily_weather_report(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'hourly' => $this->hourlyForecast(),
            ]),
            'https://api.telegram.org/bot*' => Http::response(['ok' => true]),
        ]);

        dispatch_sync(new DailyReportJob);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'Guten Morgen ☀️')
            && str_contains($request['text'], 'Wettervorhersage')
            && str_contains($request['text'], 'Morgen')
            && str_contains($request['text'], 'Mittag')
            && str_contains($request['text'], 'Abend'));
    }

    public function test_sends_fallback_when_weather_api_fails(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response('', 500),
            'https://api.telegram.org/bot*' => Http::response(['ok' => true]),
        ]);

        dispatch_sync(new DailyReportJob);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'Guten Morgen!')
            && str_contains($request['text'], 'nicht abrufen'));
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
