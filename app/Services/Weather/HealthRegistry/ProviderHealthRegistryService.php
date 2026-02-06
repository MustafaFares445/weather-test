<?php

namespace App\Services\Weather\HealthRegistry;

use App\Services\Weather\Providers\BaseWeatherProviderService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Tracks weather provider health using a failure counter stored in cache.
 *
 * Acts as a lightweight circuit-breaker:
 * - Each provider failure increments a cached counter
 * - Providers are considered "down" once a configurable threshold is reached
 * - Counters automatically expire after a TTL to allow recovery
 */
class ProviderHealthRegistryService extends BaseProviderHealthRegistryService
{
    public function __construct(protected Cache $cache) {}

    /**
     * @throws InvalidArgumentException
     */
    public function isDown(BaseWeatherProviderService $provider): bool
    {
        return $this->failureCount($provider) >= config('weather.provider_health.failure_threshold');
    }

    public function reportFailure(BaseWeatherProviderService $provider): void
    {
        $key = $this->getKey($provider);
        $updated = $this->cache->increment($key);

        $this->cache->put(
            key:$key,
            value: $updated,
            ttl: config('weather.provider_health.ttl')
        );
    }
}
