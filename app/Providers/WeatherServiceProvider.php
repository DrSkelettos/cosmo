<?php

namespace App\Providers;

use App\Services\Weather\WeatherService;
use Illuminate\Support\ServiceProvider;

class WeatherServiceProvider extends ServiceProvider
{
    /**
     * Register any weather related services.
     */
    public function register(): void
    {
        $this->app->singleton(WeatherService::class, function ($app) {
            $config = $app['config']['weather'];

            return new WeatherService(
                latitude: (float) $config['latitude'],
                longitude: (float) $config['longitude'],
                cacheTtl: (int) $config['cache_ttl'],
                baseUrl: (string) $config['base_url'],
            );
        });
    }

    /**
     * Bootstrap any weather related services.
     */
    public function boot(): void
    {
        //
    }
}
