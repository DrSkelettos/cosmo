<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        config([
            'telegram.bot_token' => 'test-token',
            'telegram.secret_token' => 'super-secret',
            'telegram.owner_id' => 123456,
            'telegram.parse_mode' => 'MarkdownV2',
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
        $response = $this->postJson('/webhooks/telegram', $this->update(999, '/help'), $this->headers());

        $response->assertStatus(204);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }

    public function test_routes_ping_command(): void
    {
        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/ping'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage') && $request['text'] === 'Pong');
    }

    public function test_routes_help_command(): void
    {
        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/help'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage') && str_contains($request['text'], 'Available commands'));
    }

    public function test_replies_to_unknown_command(): void
    {
        $response = $this->postJson('/webhooks/telegram', $this->update(123456, '/unknown'), $this->headers());

        $response->assertStatus(204);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'Unknown command'));
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
