<?php

namespace App\Services\Weather;

use App\Data\WeatherData;
use App\Data\WeatherRequestData;
use App\Exceptions\AllProvidersFailedException;
use App\Exceptions\WeatherProviderException;
use App\Http\Resources\WeatherResource;
use App\Services\Weather\Cache\WeatherCacheService;
use App\Services\Weather\Providers\BaseWeatherProviderService;
use Illuminate\Support\Facades\Log;

final readonly class WeatherService
{
    /**
     * @param iterable<BaseWeatherProviderService> $providers
     */
    public function __construct(
        private iterable $providers,
        private WeatherCacheService $cacheService
    ){}

    /**
     *  Get weather data for a city with fallback logic.
     *
     * @throws AllProvidersFailedException
     *  1. Check fresh cache first
     *  2. Try each provider in order until one succeeds
     *  3. Fall back to stale cache if all providers fail
     *  4. Throw exception if no data available
     */
    public function getWeatherData(WeatherRequestData $requestData) : WeatherData
    {
        // 1. Check fresh cache first
        $freshData = $this->cacheService->getFresh($requestData->city);
        if ($freshData){
            return  $freshData;
        }

        // 2. Try providers in order
        foreach ($this->providers as $provider){
            try {
                $weatherData = $provider->fetch($requestData->city);
                $this->cacheService->store($weatherData);

                return $weatherData;
            }catch (WeatherProviderException $e){
                Log::warning('Weather provider failed', [
                    'provider' => $provider->getName(),
                    'city' => $requestData->city,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. Fallback to stale cache
        $staelData = $this->cacheService->getStale($requestData->city);
        if ($staelData) {
            return $staelData;
        }

        // 4. No data available at all
        Log::error('All weather providers failed and no cached data available', [
            'city' => $requestData->city,
        ]);

        throw new AllProvidersFailedException($requestData->city);
    }
}
