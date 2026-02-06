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
        $cashed = Cache::get($key);

        if (!$cashed) {
            return null;
        }

        return WeatherData::from($cashed);
    }

    public function getStale(string $city): ?WeatherData
    {
        $key = $this->getStaleKey($city);
        $cached = Cache::get($key);

        if (!$cached) {
            return null;
        }

        return WeatherData::from($cached);
    }

    public function store(WeatherData $data): void
    {
        // Store in fresh cache with TTL
        Cache::put(
            $this->getFreshKey($data->city),
            $data->toArray(),
            $this->freshTtl,
        );

        // Store in stale cache (indefinitely or with configured TTL)
        $this->staleTtl ?
            Cache::forever($this->getStaleKey($data->city), $data->toArray()) :
            Cache::put($this->getStaleKey($data->city), $data->toArray(), $this->staleTtl);
    }

    public function forget(string $city): void
    {
        Cache::forget($this->getFreshKey($city));
        Cache::forget($this->getStaleKey($city));
    }
}
