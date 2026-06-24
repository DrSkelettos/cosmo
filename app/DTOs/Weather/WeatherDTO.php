<?php

namespace App\DTOs\Weather;

readonly class WeatherDTO
{
    /**
     * @param  float  $temperature  Air temperature in °C.
     * @param  float  $apparentTemperature  Feels-like temperature in °C.
     * @param  string  $condition  Human-readable weather condition.
     * @param  int|null  $precipitationProbability  Chance of precipitation in percent, if available.
     * @param  float  $windSpeed  Wind speed in km/h.
     * @param  string  $emoji  Optional emoji summarising the condition.
     */
    public function __construct(
        public float $temperature,
        public float $apparentTemperature,
        public string $condition,
        public ?int $precipitationProbability,
        public float $windSpeed,
        public string $emoji = '',
    ) {
        //
    }

    /**
     * Convert a WMO weather code to a human-readable description.
     *
     * @see https://open-meteo.com/en/docs
     */
    public static function conditionFromCode(int $code): string
    {
        return match ($code) {
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45, 48 => 'Foggy',
            51, 53, 55 => 'Drizzle',
            56, 57 => 'Freezing drizzle',
            61, 63, 65 => 'Rain',
            66, 67 => 'Freezing rain',
            71, 73, 75 => 'Snow',
            77 => 'Snow grains',
            80, 81, 82 => 'Rain showers',
            85, 86 => 'Snow showers',
            95 => 'Thunderstorm',
            96, 99 => 'Thunderstorm with hail',
            default => 'Unknown',
        };
    }

    /**
     * Convert a WMO weather code to a German description.
     */
    public static function conditionGermanFromCode(int $code): string
    {
        return match ($code) {
            0 => 'Klar',
            1 => 'Überwiegend klar',
            2 => 'Teilweise bewölkt',
            3 => 'Bedeckt',
            45, 48 => 'Neblig',
            51, 53, 55 => 'Nieselregen',
            56, 57 => 'Gefrierender Nieselregen',
            61, 63, 65 => 'Regen',
            66, 67 => 'Gefrierender Regen',
            71, 73, 75 => 'Schnee',
            77 => 'Schneekörner',
            80, 81, 82 => 'Regenschauer',
            85, 86 => 'Schneeschauer',
            95 => 'Gewitter',
            96, 99 => 'Gewitter mit Hagel',
            default => 'Unbekannt',
        };
    }

    /**
     * Convert a WMO weather code to an appropriate emoji.
     */
    public static function emojiFromCode(int $code): string
    {
        return match ($code) {
            0, 1 => '☀️',
            2 => '🌤️',
            3 => '☁️',
            45, 48 => '🌫️',
            51, 53, 55, 56, 57 => '🌦️',
            61, 63, 65, 66, 67, 80, 81, 82 => '🌧️',
            71, 73, 75, 77, 85, 86 => '❄️',
            95, 96, 99 => '⛈️',
            default => '🌡️',
        };
    }
}
