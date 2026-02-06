<?php

namespace App\Services\Weather\Cache;

use AllowDynamicProperties;
use App\Data\WeatherData;
use Illuminate\Support\Facades\Cache;

class WeatherCacheService extends BaseWeatherCacheService
{
    public function __construct()
    {
        $this->prefix = config('weather.cache.prefix', 'weather');
        $this->freshTtl = config('weather.cache.fresh_ttl', 1800); // 30 minutes
        $this->staleTtl = config('weather.cache.stale_ttl'); // null = forever
    }

    public function getFresh(string $city): ?WeatherData
    {
        $key = $this->getFreshKey($city);

        return $this->getCache($key);
    }

    public function getStale(string $city): ?WeatherData
    {
        $key = $this->getStaleKey($city);

        return $this->getCache($key);
    }

    public function store(WeatherData $data): void
    {
        $payload = $data->toArray();
        $payload['fetched_at'] = $data->fetched_at->format('Y-m-d H:i:s');

        // Store in fresh cache with TTL
        Cache::put(
            $this->getFreshKey($data->city),
            $payload,
            $this->freshTtl,
        );

        // Store in stale cache (indefinitely or with configured TTL)
        $this->staleTtl === null ?
            Cache::forever($this->getStaleKey($data->city), $payload) :
            Cache::put($this->getStaleKey($data->city), $payload, $this->staleTtl);
    }

    public function forget(string $city): void
    {
        Cache::forget($this->getFreshKey($city));
        Cache::forget($this->getStaleKey($city));
    }
}
