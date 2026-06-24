<?php

namespace App\Telegram\Commands;

use App\Services\Telegram\TelegramService;
use App\Telegram\Contracts\Command;

class PingCommand implements Command
{
    public static function name(): string
    {
        return 'ping';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, TelegramService $telegram): void
    {
        $message = $payload['message'];
        $chatId = (int) $message['chat']['id'];

        $telegram->sendMessage($chatId, 'Pong');
    }
}
