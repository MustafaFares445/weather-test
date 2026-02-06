<?php

namespace App\Services\Weather\HealthRegistry;

use App\Services\Weather\Providers\BaseWeatherProviderService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Base contract for provider health registries.
 *
 * A provider health registry tracks failures for external weather providers
 * and determines whether a provider should be temporarily excluded
 * from execution (circuit-breaker behavior).
 */
abstract class BaseProviderHealthRegistryService
{
    /**
     * Cache repository used to persist provider failure state.
     *
     * Typically backed by Redis for atomic increments and TTL support.
     */
    protected Cache $cache;

    /**
     * Determine whether the given provider is currently unhealthy.
     *
     * Implementations decide what constitutes a "down" provider
     * (e.g. failure thresholds, rolling windows, exponential backoff).
     *
     * @param  BaseWeatherProviderService $provider
     * @return bool True if the provider should be skipped
     */
    abstract public function isDown(BaseWeatherProviderService $provider): bool;

    /**
     * Record a failure for the given provider.
     *
     * This method is expected to be called only when a provider request
     * definitively fails (timeouts, 5xx, invalid responses, etc.).
     *
     * Implementations may increment counters, refresh TTLs,
     * or trigger other degradation logic.
     *
     * @param BaseWeatherProviderService $provider
     */
    abstract public function reportFailure(BaseWeatherProviderService $provider): void;

    /**
     * Retrieve the current failure count for a provider.
     *
     * Shared helper for cache-backed implementations.
     *
     * @param  BaseWeatherProviderService $provider
     * @return int Number of recorded failures
     *
     * @throws InvalidArgumentException When the cache key is invalid
     */
    protected function failureCount(BaseWeatherProviderService $provider): int
    {
        return (int) $this->cache->get($this->getKey($provider), 0);
    }

    /**
     * Build the cache key used to store provider failure state.
     *
     * Key format is stable and namespaced to avoid collisions.
     *
     * @param  BaseWeatherProviderService $provider
     * @return string Cache key for the provider
     */
    protected function getKey(BaseWeatherProviderService $provider): string
    {
        return 'weather:provider:failures:' . $provider->getName();
    }
}
