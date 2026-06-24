<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Location
    |--------------------------------------------------------------------------
    |
    | The latitude and longitude used when querying Open-Meteo. These should
    | point to the location you want the bot to report on by default.
    |
    */

    'latitude' => (float) env('WEATHER_LAT', 0.0),

    'longitude' => (float) env('WEATHER_LON', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Open-Meteo API Endpoint
    |--------------------------------------------------------------------------
    |
    | The base URL for the Open-Meteo forecast API. You normally do not need
    | to change this.
    |
    */

    'base_url' => env('WEATHER_API_URL', 'https://api.open-meteo.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Response Cache TTL
    |--------------------------------------------------------------------------
    |
    | Number of seconds to cache Open-Meteo responses. The default is 15 minutes.
    |
    */

    'cache_ttl' => (int) env('WEATHER_CACHE_TTL', 900),

];
