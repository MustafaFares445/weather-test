<?php

namespace App\Services\Weather\HealthRegistry;

use App\Data\WeatherData;
use App\Data\WeatherRequestData;
use App\Exceptions\WeatherProviderException;
use App\Services\Weather\Providers\BaseWeatherProviderService;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Orchestrates weather provider execution with health-aware short-circuiting.
 */
readonly class WeatherProviderPoolService
{
    /**
     * @param iterable<BaseWeatherProviderService> $providers
     *        Ordered list of weather providers (priority-based)
     * @param ProviderHealthRegistryService $healthRegistry
     *        Provider health / circuit-breaker registry
     */
    public function __construct(
        private iterable $providers,
        private ProviderHealthRegistryService $healthRegistry,
    ) {}

    /**
     * Fetch weather data from the first healthy provider that succeeds.
     *
     * Providers are evaluated in order:
     * 1. Skip providers currently marked as unhealthy
     * 2. Attempt to fetch data
     * 3. On failure, record the failure and continue
     * 4. Return immediately on first success
     *
     * @param  WeatherRequestData $request
     * @return WeatherData|null Null when all providers fail or are unhealthy
     *
     * @throws InvalidArgumentException When provider health cache access fails
     */
    public function fetchFirstSuccessful(WeatherRequestData $request): ?WeatherData
    {
        foreach ($this->providers as $provider) {
            if ($this->healthRegistry->isDown($provider)) {
                continue;
            }

            try {
                return $provider->fetch($request->city);
            } catch (WeatherProviderException $e) {
                $this->healthRegistry->reportFailure($provider);

                Log::warning('Weather provider failed', [
                    'provider' => $provider->getName(),
                    'city'     => $request->city,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return null;
    }
}
