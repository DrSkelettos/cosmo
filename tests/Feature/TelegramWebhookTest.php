<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telegram.bot_token' => 'test-token',
            'telegram.secret_token' => 'super-secret',
            'telegram.owner_id' => 123456,
            'telegram.parse_mode' => 'MarkdownV2',
            'weather.latitude' => 52.52,
            'weather.longitude' => 13.41,
            'weather.cache_ttl' => 900,
            'weather.base_url' => 'https://api.open-meteo.com/v1',
        ]);
    }

    public function test_rejects_missing_secret_token(): void
    {
        $response = $this->postJson('/webhooks/telegram', []);

        $response->assertStatus(401);
    }

    public function test_rejects_invalid_secret_token(): void
    {
        $response = $this->postJson('/webhooks/telegram', [], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_ignores_messages_from_non_owner(): void
    {
        $this->fakeHttp();

        $response = $this->postJson('/webhooks/telegram', $this->update(999, '/help'), $this->headers());

        $response->assertStatus(204);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }

    public function test_routes_ping_command(): void
    {
        $this->fakeHttp();

        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/ping'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage') && $request['text'] === 'Pong');
    }

    public function test_routes_help_command(): void
    {
        $this->fakeHttp();

        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/help'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'Available commands')
            && str_contains($request['text'], '/daily'));
    }

    public function test_replies_to_unknown_command(): void
    {
        $this->fakeHttp();

        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/unknown'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Unknown command'));
    }

    public function test_routes_weather_command(): void
    {
        $this->fakeHttp([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'hourly' => $this->hourlyForecast(),
            ]),
        ]);

        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/weather'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'Wettervorhersage')
            && str_contains($request['text'], 'Morgen')
            && str_contains($request['text'], 'Mittag')
            && str_contains($request['text'], 'Abend'));
    }

    public function test_routes_daily_command(): void
    {
        $this->fakeHttp([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'hourly' => $this->hourlyForecast(),
            ]),
        ]);

        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/daily'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'Guten Morgen ☀️')
            && str_contains($request['text'], 'Wettervorhersage'));
    }

    public function test_daily_command_sends_fallback_when_api_fails(): void
    {
        $this->fakeHttp([
            'https://api.open-meteo.com/v1/forecast*' => Http::response('', 500),
        ]);

        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/daily'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'Guten Morgen!')
            && str_contains($request['text'], 'nicht abrufen'));
    }

    public function test_weather_command_sends_fallback_when_api_fails(): void
    {
        $this->fakeHttp([
            'https://api.open-meteo.com/v1/forecast*' => Http::response('', 500),
        ]);

        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/weather'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'Entschuldigung'));
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

    private function fakeHttp(array $stubs = []): void
    {
        Http::fake(array_merge($stubs, ['*' => Http::response(['ok' => true])]));
    }

    private function headers(): array
    {
        return ['X-Telegram-Bot-Api-Secret-Token' => 'super-secret'];
    }

    private function update(int $userId, string $text): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'from' => ['id' => $userId, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => $userId, 'type' => 'private'],
                'date' => time(),
                'text' => $text,
            ],
        ];
    }
}
