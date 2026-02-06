<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Weather Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the weather API providers used by the aggregator.
    |
    */

    'providers' => [
        'openweather' => [
            'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
            'api_key' => env('OPENWEATHER_API_KEY'),
        ],

        'weatherstack' => [
            'base_url' => env('WEATHERSTACK_BASE_URL', 'http://api.weatherstack.com'),
            'api_key' => env('WEATHERSTACK_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configure HTTP client settings for API requests.
    |
    */

    'http' => [
        'timeout' => env('WEATHER_HTTP_TIMEOUT', 5),
        'retry_times' => env('WEATHER_RETRY_TIMES', 0),
        'retry_sleep' => env('WEATHER_RETRY_SLEEP', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for weather data.
    |
    */

    'cache' => [
        'fresh_ttl' => env('WEATHER_CACHE_FRESH_TTL', 1800), // 30 minutes in seconds
        'stale_ttl' => null, // null = forever (indefinite)
        'prefix' => 'weather',
    ],
];

