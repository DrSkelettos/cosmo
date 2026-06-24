<?php

namespace App\Telegram\Commands;

use App\Services\Telegram\TelegramService;
use App\Services\Weather\WeatherMessageFormatter;
use App\Services\Weather\WeatherService;
use App\Telegram\Contracts\Command;

class WeatherCommand implements Command
{
    public function __construct(protected WeatherService $weather)
    {
        //
    }

    public static function name(): string
    {
        return 'weather';
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
                'Entschuldigung, ich konnte die Wettervorhersage aktuell nicht abrufen. Bitte versuche es später erneut.'
            );

            return;
        }

        $text = WeatherMessageFormatter::formatCommandSegments($segments, $telegram);

        $telegram->sendMessage($chatId, $text);
    }
}
