<?php

namespace App\Telegram\Commands;

use App\Services\Telegram\TelegramService;
use App\Services\Weather\WeatherMessageFormatter;
use App\Services\Weather\WeatherService;
use App\Telegram\Contracts\Command;

class DailyCommand implements Command
{
    public function __construct(protected WeatherService $weather)
    {
        //
    }

    public static function name(): string
    {
        return 'daily';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, TelegramService $telegram): void
    {
        $message = $payload['message'];
        $chatId = (int) $message['chat']['id'];

        $segments = $this->weather->forecastSegments();

        if ($segments === null) {
            $telegram->sendMessage(
                $chatId,
                'Guten Morgen! Ich konnte die Wettervorhersage aktuell nicht abrufen. Bitte versuche es später erneut.'
            );

            return;
        }

        $telegram->sendMessage($chatId, WeatherMessageFormatter::formatDailyReport($segments, $telegram));
    }
}
