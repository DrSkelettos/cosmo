<?php

namespace App\Jobs;

use App\Services\Telegram\TelegramService;
use App\Services\Weather\WeatherMessageFormatter;
use App\Services\Weather\WeatherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DailyReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(WeatherService $weather, TelegramService $telegram): void
    {
        $chatId = (int) config('telegram.owner_id');

        if ($chatId === 0) {
            Log::channel('telegram')->warning('DailyReportJob: no Telegram owner configured');

            return;
        }

        $segments = $weather->forecastSegments();

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
