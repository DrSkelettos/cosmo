<?php

namespace App\Providers;

use App\Services\Telegram\TelegramService;
use App\Telegram\CommandRouter;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Register any Telegram related services.
     */
    public function register(): void
    {
        $this->app->singleton(TelegramService::class, function ($app) {
            $config = $app['config']['telegram'];

            return new TelegramService(
                botToken: (string) $config['bot_token'],
                apiUrl: (string) $config['api_url'],
                parseMode: (string) $config['parse_mode'],
            );
        });

        $this->app->singleton(CommandRouter::class, function ($app) {
            return new CommandRouter($app->make(TelegramService::class));
        });
    }

    /**
     * Bootstrap any Telegram related services.
     */
    public function boot(): void
    {
        //
    }
}
