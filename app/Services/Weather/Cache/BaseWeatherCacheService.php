<?php

namespace App\Services\Weather\Cache;

use App\Data\WeatherData;
use Illuminate\Support\Str;

abstract class BaseWeatherCacheService
{
    protected string $prefix;
    protected int $freshTtl;
    protected ?int $staleTtl;

    /**
     * Get fresh cached weather data for a city.
     */
   abstract public function getFresh(string $city): ?WeatherData;

    /**
     * Get stale cached weather data for a city.
     */
    abstract public function getStale(string $city): ?WeatherData;

    /**
     * Store weather data in the cache.
     */
    abstract public function store(WeatherData $data): void;

    /**
     * Clear all cached data for a city.
     */
    abstract public function forget(string $city): void;


    /**
     * Generate the fresh cache key for a city.
     */
    protected function getFreshKey(string $city): string
    {
        return "$this->prefix:fresh:" . $this->normalizeCity($city);
    }

    /**
     * Generate the stale cache key for a city.
     */
    protected function getStaleKey(string $city): string
    {
        return "$this->prefix:stale:" . $this->normalizeCity($city);
    }

    /**
     * Normalize city name for consistent cache keys.
     */
    private function normalizeCity(string $city): string
    {
        return Str::slug($city);
    }
}
