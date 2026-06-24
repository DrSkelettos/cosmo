<?php

namespace App\Services\Weather;

use App\DTOs\Weather\WeatherDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherService
{
    public function __construct(
        protected float $latitude,
        protected float $longitude,
        protected int $cacheTtl,
        protected string $baseUrl,
    ) {
        //
    }

    /**
     * Fetch the current weather conditions for the configured location.
     */
    public function current(): ?WeatherDTO
    {
        return $this->fetchCached('current', fn () => $this->fetchCurrent());
    }

    /**
     * Fetch today's forecast for the configured location.
     */
    public function today(): ?WeatherDTO
    {
        return $this->fetchCached('today', fn () => $this->fetchToday());
    }

    /**
     * Fetch a segmented day forecast (morning, midday, evening) for the configured location.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function forecastSegments(): ?array
    {
        $key = $this->cacheKey('segments');
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $value = $this->fetchForecastSegments();

        if (is_array($value)) {
            Cache::put($key, $value, $this->cacheTtl);
        }

        return $value;
    }

    /**
     * Return a cached value or fetch it, only caching successful responses.
     */
    protected function fetchCached(string $type, callable $fetch): ?WeatherDTO
    {
        $key = $this->cacheKey($type);
        $cached = Cache::get($key);

        if ($cached instanceof WeatherDTO) {
            return $cached;
        }

        $value = $fetch();

        if ($value !== null) {
            Cache::put($key, $value, $this->cacheTtl);
        }

        return $value;
    }

    /**
     * Build a cache key tied to the configured location.
     */
    protected function cacheKey(string $type): string
    {
        return "weather:{$type}:{$this->latitude},{$this->longitude}";
    }

    /**
     * Query Open-Meteo for the current weather.
     */
    protected function fetchCurrent(): ?WeatherDTO
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/forecast", [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'current' => 'temperature_2m,apparent_temperature,weather_code,wind_speed_10m,precipitation',
                'daily' => 'precipitation_probability_max',
                'forecast_days' => 1,
                'timezone' => 'auto',
            ]);

            if (! $response->successful()) {
                Log::error('Open-Meteo current weather request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $current = $data['current'] ?? null;
            $daily = $data['daily'] ?? null;

            if (! is_array($current) || ! isset($current['weather_code'])) {
                Log::error('Open-Meteo current weather response missing expected data');

                return null;
            }

            $code = (int) $current['weather_code'];

            return new WeatherDTO(
                temperature: (float) ($current['temperature_2m'] ?? 0),
                apparentTemperature: (float) ($current['apparent_temperature'] ?? 0),
                condition: WeatherDTO::conditionFromCode($code),
                precipitationProbability: isset($daily['precipitation_probability_max'][0])
                    ? (int) $daily['precipitation_probability_max'][0]
                    : null,
                windSpeed: (float) ($current['wind_speed_10m'] ?? 0),
                emoji: WeatherDTO::emojiFromCode($code),
            );
        } catch (Throwable $exception) {
            Log::error('Open-Meteo current weather request threw an exception', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Query Open-Meteo for today's daily forecast.
     */
    protected function fetchToday(): ?WeatherDTO
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/forecast", [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'daily' => 'temperature_2m_max,temperature_2m_min,apparent_temperature_max,weather_code,precipitation_probability_max,wind_speed_10m_max',
                'forecast_days' => 1,
                'timezone' => 'auto',
            ]);

            if (! $response->successful()) {
                Log::error('Open-Meteo today forecast request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $daily = $data['daily'] ?? null;

            if (! is_array($daily) || ! isset($daily['weather_code'][0])) {
                Log::error('Open-Meteo today forecast response missing expected data');

                return null;
            }

            $code = (int) $daily['weather_code'][0];

            return new WeatherDTO(
                temperature: (float) ($daily['temperature_2m_max'][0] ?? 0),
                apparentTemperature: (float) ($daily['apparent_temperature_max'][0] ?? 0),
                condition: WeatherDTO::conditionFromCode($code),
                precipitationProbability: isset($daily['precipitation_probability_max'][0])
                    ? (int) $daily['precipitation_probability_max'][0]
                    : null,
                windSpeed: (float) ($daily['wind_speed_10m_max'][0] ?? 0),
                emoji: WeatherDTO::emojiFromCode($code),
            );
        } catch (Throwable $exception) {
            Log::error('Open-Meteo today forecast request threw an exception', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Query Open-Meteo for an hourly day forecast split into morning, midday and evening.
     *
     * @return array<int, array<string, mixed>>|null
     */
    protected function fetchForecastSegments(): ?array
    {
        try {
            $response = Http::timeout(15)->get("{$this->baseUrl}/forecast", [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'hourly' => 'temperature_2m,weather_code,precipitation_probability,precipitation',
                'forecast_days' => 1,
                'timezone' => 'auto',
            ]);

            if (! $response->successful()) {
                Log::error('Open-Meteo hourly forecast request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $hourly = $data['hourly'] ?? null;

            if (! is_array($hourly) || ! isset($hourly['time'][0])) {
                Log::error('Open-Meteo hourly forecast response missing expected data');

                return null;
            }

            $times = $hourly['time'];
            $temperatures = $hourly['temperature_2m'] ?? [];
            $codes = $hourly['weather_code'] ?? [];
            $probabilities = $hourly['precipitation_probability'] ?? [];
            $precipitations = $hourly['precipitation'] ?? [];

            $startOfDay = 0;
            foreach ($times as $index => $time) {
                if (str_ends_with($time, 'T00:00')) {
                    $startOfDay = $index;
                    break;
                }
            }

            $segments = [
                ['label' => 'Morgen', 'offset' => 6],
                ['label' => 'Mittag', 'offset' => 12],
                ['label' => 'Abend', 'offset' => 18],
            ];

            $result = [];
            foreach ($segments as $segment) {
                $start = $startOfDay + $segment['offset'];
                $end = $start + 5;

                if (! isset($temperatures[$start], $temperatures[$end], $codes[$start])) {
                    Log::error('Open-Meteo hourly forecast missing segment data', [
                        'segment' => $segment['label'],
                    ]);

                    return null;
                }

                $segmentTemps = array_slice($temperatures, $start, 6);
                $segmentCodes = array_slice($codes, $start, 6);
                $segmentProbs = array_slice($probabilities, $start, 6);
                $segmentPrecip = array_slice($precipitations, $start, 6);

                $minTemp = min($segmentTemps);
                $maxTemp = max($segmentTemps);
                $representativeIndex = intdiv(count($segmentCodes), 2);
                $representativeCode = (int) ($segmentCodes[$representativeIndex] ?? 0);
                $maxProbability = max(array_map(fn ($value) => (int) ($value ?? 0), $segmentProbs));
                $totalPrecipMm = (float) array_sum(array_map(fn ($value) => (float) ($value ?? 0.0), $segmentPrecip));

                $result[] = [
                    'label' => $segment['label'],
                    'minTemp' => (float) $minTemp,
                    'maxTemp' => (float) $maxTemp,
                    'condition' => WeatherDTO::conditionGermanFromCode($representativeCode),
                    'precipitationProbability' => $maxProbability,
                    'precipitationMl' => (float) round($totalPrecipMm * 1000, 1),
                ];
            }

            return $result;
        } catch (Throwable $exception) {
            Log::error('Open-Meteo hourly forecast request threw an exception', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
