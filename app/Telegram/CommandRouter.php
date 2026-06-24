<?php

namespace App\Telegram;

use App\Services\Telegram\TelegramService;
use App\Telegram\Commands\DailyCommand;
use App\Telegram\Commands\HelpCommand;
use App\Telegram\Commands\PingCommand;
use App\Telegram\Commands\WeatherCommand;
use App\Telegram\Contracts\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CommandRouter
{
    /**
     * @var array<string, class-string<Command>>
     */
    protected array $commands = [
        'help' => HelpCommand::class,
        'ping' => PingCommand::class,
        'weather' => WeatherCommand::class,
        'daily' => DailyCommand::class,
    ];

    public function __construct(protected TelegramService $telegram)
    {
        //
    }

    /**
     * Route a Telegram update to the appropriate command handler.
     *
     * @param  array<string, mixed>  $update
     */
    public function route(array $update): void
    {
        $message = $update['message'] ?? null;

        if (! is_array($message)) {
            return;
        }

        $text = $message['text'] ?? '';

        if (! str_starts_with($text, '/')) {
            return;
        }

        $parts = explode(' ', $text);
        $command = strtolower(ltrim($parts[0], '/'));
        $arguments = array_slice($parts, 1);

        Log::channel('telegram')->info('Incoming command', [
            'command' => $command,
            'from' => $message['from']['id'] ?? null,
            'username' => $message['from']['username'] ?? null,
            'text' => $text,
        ]);

        if (! isset($this->commands[$command])) {
            $this->telegram->sendMessage(
                (int) $message['chat']['id'],
                'Unknown command: use /help to see the available commands'
            );

            return;
        }

        try {
            /** @var Command $handler */
            $handler = app()->make($this->commands[$command]);

            $handler->handle([
                'command' => $command,
                'arguments' => $arguments,
                'message' => $message,
            ], $this->telegram);
        } catch (Throwable $exception) {
            Log::channel('telegram')->error('Command execution failed', [
                'command' => $command,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Register a new command mapping.
     *
     * @param  class-string<Command>  $handler
     */
    public function register(string $command, string $handler): void
    {
        $this->commands[$command] = $handler;
    }
}
