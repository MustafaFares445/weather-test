<?php

namespace App\Services\Weather;

use App\Data\WeatherData;
use App\Data\WeatherRequestData;
use App\Exceptions\AllProvidersFailedException;
use App\Jobs\RefreshWeatherCacheJob;
use App\Services\Weather\Cache\WeatherCacheService;
use App\Services\Weather\HealthRegistry\WeatherProviderPoolService;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;

final readonly class WeatherService
{
    public function __construct(
        private WeatherCacheService        $cache,
        private WeatherProviderPoolService $providerPool,
    ) {}

    /**
     *  Get weather data for a city with fallback logic.
     *
     * @throws AllProvidersFailedException
     * @throws InvalidArgumentException
     *  1. Check fresh cache first
     *  2. Try each provider in order until one succeeds
     *  3. Fall back to stale cache if all providers fail
     *  4. Throw exception if no data available
     */
    public function getWeatherData(WeatherRequestData $requestData) : WeatherData
    {
        // 1. Check fresh cache first
        $freshData = $this->cache->getFresh($requestData->city);
        if ($freshData){
            return  $freshData;
        }

        // 2. Try providers via pool
        $weatherData = $this->providerPool->fetchFirstSuccessful($requestData);
        if ($weatherData) {
            dispatch(new RefreshWeatherCacheJob($weatherData));

            return $weatherData;
        }

        // 3. Fallback to stale cache
        $staleData = $this->cache->getStale($requestData->city);
        if ($staleData) {
            return $staleData;
        }

        // 4. No data available at all
        Log::error('All weather providers failed and no cached data available', [
            'city' => $requestData->city,
        ]);

        throw new AllProvidersFailedException($requestData->city);
    }
}
