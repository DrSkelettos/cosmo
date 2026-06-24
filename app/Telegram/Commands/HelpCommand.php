<?php

namespace App\Telegram\Commands;

use App\Services\Telegram\TelegramService;
use App\Telegram\Contracts\Command;

class HelpCommand implements Command
{
    public static function name(): string
    {
        return 'help';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, TelegramService $telegram): void
    {
        $message = $payload['message'];
        $chatId = (int) $message['chat']['id'];

        $text = $telegram->formatBold('Available commands')."\n\n"
            ."/help: Show this help message\n"
            .'/ping: Check bot responsiveness';

        $telegram->sendMessage($chatId, $text);
    }
}
