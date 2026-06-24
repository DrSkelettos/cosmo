<?php

use App\Providers\AppServiceProvider;
use App\Providers\TelegramServiceProvider;
use App\Providers\WeatherServiceProvider;

return [
    AppServiceProvider::class,
    TelegramServiceProvider::class,
    WeatherServiceProvider::class,
];
