<?php

namespace App\Telegram;

use App\Ai\Agents\ChatAgent;
use App\Services\Telegram\TelegramService;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Throwable;

class ChatHandler
{
    /**
     * Minimum interval (in milliseconds) between message edits.
     */
    protected int $editIntervalMs = 1500;

    public function __construct(protected TelegramService $telegram)
    {
        //
    }

    /**
     * Handle a non-command message by streaming an AI response.
     *
     * @param  array<string, mixed>  $message
     */
    public function handle(array $message): void
    {
        $chatId = (int) $message['chat']['id'];
        $text = $message['text'] ?? '';

        if (trim($text) === '') {
            return;
        }

        $this->telegram->sendChatAction($chatId, 'typing');

        $messageId = $this->telegram->sendMessage($chatId, '💭');

        if ($messageId === false) {
            Log::channel('telegram')->error('Failed to send initial chat message', [
                'chat_id' => $chatId,
            ]);

            return;
        }

        try {
            $agent = new ChatAgent;
            $response = $agent->stream($text);

            $accumulated = '';
            $lastEditAt = 0;

            foreach ($response as $event) {
                if ($event instanceof TextDelta) {
                    $accumulated .= $event->delta;

                    $now = (int) (microtime(true) * 1000);

                    if ($now - $lastEditAt >= $this->editIntervalMs && trim($accumulated) !== '') {
                        $this->telegram->editMessageText($chatId, $messageId, $accumulated, [
                            'parse_mode' => '',
                        ]);
                        $lastEditAt = $now;
                    }
                }

                if ($event instanceof StreamEnd) {
                    break;
                }
            }

            if (trim($accumulated) === '') {
                $accumulated = 'No response generated.';
            }

            $this->telegram->editMessageText($chatId, $messageId, $accumulated, [
                'parse_mode' => '',
            ]);
        } catch (Throwable $exception) {
            Log::channel('telegram')->error('Chat agent streaming failed', [
                'chat_id' => $chatId,
                'error' => $exception->getMessage(),
            ]);

            $this->telegram->editMessageText($chatId, $messageId, 'Sorry, I could not generate a response. Please try again.', [
                'parse_mode' => '',
            ]);
        }
    }
}
