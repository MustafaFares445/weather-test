<?php

namespace App\Providers;

use App\Services\Weather\Cache\WeatherCacheService;
use App\Services\Weather\Providers\OpenWeatherProviderService;
use App\Services\Weather\Providers\WeatherStackProviderService;
use App\Services\Weather\WeatherService;
use Carbon\Laravel\ServiceProvider;

class WeatherServiceProvider extends ServiceProvider
{
    public function register() : void
    {
        $this->app->singleton(OpenWeatherProviderService::class);
        $this->app->singleton(WeatherStackProviderService::class);

        $this->app->tag([
            OpenWeatherProviderService::class,
            WeatherStackProviderService::class,
        ], 'weather.providers');

        $this->app->singleton(WeatherCacheService::class);

        $this->app->singleton(WeatherService::class, function ($app) {
            return new WeatherService(
                providers: $app->tagged('weather.providers'),
                cacheService: $app->make(WeatherCacheService::class),
            );
        });
    }

    public function boot() : void{}

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            OpenWeatherProviderService::class,
            WeatherStackProviderService::class,
        ];
    }
}
