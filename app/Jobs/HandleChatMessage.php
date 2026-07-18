<?php

namespace App\Jobs;

use App\Ai\Agents\ChatAgent;
use App\Services\Telegram\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Throwable;

class HandleChatMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Minimum interval (in milliseconds) between message edits.
     */
    protected int $editIntervalMs = 1500;

    /**
     * @param  array<string, mixed>  $message
     */
    public function __construct(protected array $message)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegram): void
    {
        $chatId = (int) $this->message['chat']['id'];
        $text = $this->message['text'] ?? '';

        if (trim($text) === '') {
            return;
        }

        $telegram->sendChatAction($chatId, 'typing');

        $messageId = $telegram->sendMessage($chatId, '💭');

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
                        $telegram->editMessageText($chatId, $messageId, $accumulated, [
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

            $telegram->editMessageText($chatId, $messageId, $accumulated, [
                'parse_mode' => '',
            ]);
        } catch (Throwable $exception) {
            Log::channel('telegram')->error('Chat agent streaming failed', [
                'chat_id' => $chatId,
                'error' => $exception->getMessage(),
            ]);

            $telegram->editMessageText($chatId, $messageId, 'Sorry, I could not generate a response. Please try again.', [
                'parse_mode' => '',
            ]);
        }
    }
}
