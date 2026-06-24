<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramService
{
    public function __construct(
        protected string $botToken,
        protected string $apiUrl,
        protected string $parseMode,
    ) {
        //
    }

    /**
     * Send a text message to the given Telegram chat.
     */
    public function sendMessage(int $chatId, string $text, array $options = []): bool
    {
        if (empty($this->botToken)) {
            Log::channel('telegram')->warning('Telegram bot token is missing');

            return false;
        }

        try {
            $payload = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $this->parseMode,
                'disable_web_page_preview' => true,
            ], $options);

            $response = Http::timeout(30)
                ->post("{$this->apiUrl}{$this->botToken}/sendMessage", $payload);

            if (! $response->successful()) {
                Log::channel('telegram')->error('Telegram API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'chat_id' => $chatId,
                ]);

                return false;
            }

            $body = $response->json();
            if (! ($body['ok'] ?? false)) {
                Log::channel('telegram')->error('Telegram API returned an error', [
                    'description' => $body['description'] ?? 'unknown',
                    'chat_id' => $chatId,
                ]);

                return false;
            }

            return true;
        } catch (Throwable $exception) {
            Log::channel('telegram')->error('Telegram send message failed', [
                'chat_id' => $chatId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Register the webhook URL with Telegram.
     */
    public function setWebhook(string $url, string $secretToken): bool
    {
        if (empty($this->botToken)) {
            Log::channel('telegram')->warning('Telegram bot token is missing');

            return false;
        }

        try {
            $response = Http::timeout(30)
                ->post("{$this->apiUrl}{$this->botToken}/setWebhook", [
                    'url' => $url,
                    'secret_token' => $secretToken,
                    'max_connections' => 40,
                    'allowed_updates' => ['message'],
                ]);

            if (! $response->successful()) {
                Log::channel('telegram')->error('Telegram setWebhook request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $body = $response->json();
            if (! ($body['ok'] ?? false)) {
                Log::channel('telegram')->error('Telegram setWebhook failed', [
                    'description' => $body['description'] ?? 'unknown',
                ]);

                return false;
            }

            return true;
        } catch (Throwable $exception) {
            Log::channel('telegram')->error('Telegram setWebhook failed', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Format a string as bold text.
     */
    public function formatBold(string $text): string
    {
        return '*'.$this->escapeMarkdown($text).'*';
    }

    /**
     * Format a string as inline code.
     */
    public function formatCode(string $text): string
    {
        return '`'.$this->escapeMarkdown($text).'`';
    }

    /**
     * Escape characters that have a special meaning in MarkdownV2.
     */
    public function escapeMarkdown(string $text): string
    {
        $reserved = ['\\', '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        $escaped = ['\\\\', '\\_', '\\*', '\\[', '\\]', '\\(', '\\)', '\\~', '\\`', '\\>', '\\#', '\\+', '\\-', '\\=', '\\|', '\\{', '\\}', '\\.', '\\!'];

        return str_replace($reserved, $escaped, $text);
    }
}
