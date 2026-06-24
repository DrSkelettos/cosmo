<?php

namespace App\Services\Weather;

use App\Services\Telegram\TelegramService;

class WeatherMessageFormatter
{
    /**
     * Format the daily report with a German greeting and the segmented forecast table.
     *
     * @param  array<int, array<string, mixed>>  $segments
     */
    public static function formatDailyReport(array $segments, TelegramService $telegram): string
    {
        return "Guten Morgen ☀️\n\n".self::formatCommandSegments($segments, $telegram);
    }

    /**
     * Format a detailed German day forecast as a monospaced table.
     *
     * @param  array<int, array<string, mixed>>  $segments
     */
    public static function formatCommandSegments(array $segments, TelegramService $telegram): string
    {
        $rows = [
            ['Zeit', 'Temperatur', 'Wetter', 'Regen'],
        ];

        foreach ($segments as $segment) {
            $rows[] = [
                $segment['label'],
                round($segment['minTemp'], 0).'-'.round($segment['maxTemp'], 0).'°C',
                $segment['condition'],
                $segment['precipitationProbability'].'% ('.round($segment['precipitationMl'], 1).' ml)',
            ];
        }

        $widths = [0, 0, 0, 0];
        foreach ($rows as $row) {
            foreach ($row as $index => $cell) {
                $widths[$index] = max($widths[$index], mb_strlen($cell));
            }
        }

        $lines = [$telegram->formatBold('Wettervorhersage'), ''];
        $lines[] = '```';

        foreach ($rows as $index => $row) {
            $line = '';
            foreach ($row as $colIndex => $cell) {
                $line .= self::padCell($cell, $widths[$colIndex]).'  ';
            }
            $lines[] = rtrim($line);

            if ($index === 0) {
                $separator = '';
                foreach ($widths as $width) {
                    $separator .= str_repeat('-', $width).'  ';
                }
                $lines[] = rtrim($separator);
            }
        }

        $lines[] = '```';

        return implode("\n", $lines);
    }

    private static function padCell(string $text, int $width): string
    {
        return $text.str_repeat(' ', max(0, $width - mb_strlen($text)));
    }
}
