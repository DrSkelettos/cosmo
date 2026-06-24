<?php

namespace App\Telegram\Contracts;

use App\Services\Telegram\TelegramService;

interface Command
{
    /**
     * Return the command name without the leading slash.
     */
    public static function name(): string;

    /**
     * Handle the command.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, TelegramService $telegram): void;
}
