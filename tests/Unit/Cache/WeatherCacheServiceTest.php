<?php

use App\Data\WeatherData;
use App\Services\Weather\Cache\WeatherCacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('stores and retrieves using normalized keys', function () {
    config([
        'weather.cache.prefix' => 'weather',
        'weather.cache.fresh_ttl' => 600,
        'weather.cache.stale_ttl' => null,
    ]);

    Carbon::setTestNow('2025-01-01 00:00:00');

    $service = new WeatherCacheService();
    $data = new WeatherData(
        city: 'New York',
        temperature: 5.5,
        description: 'Clear',
        source: 'OpenWeather',
        fetched_at: Carbon::now(),
    );

    $service->store($data);

    expect(Cache::has('weather:fresh:new-york'))->toBeTrue()
        ->and(Cache::has('weather:stale:new-york'))->toBeTrue();

    $fresh = $service->getFresh('New York');
    expect($fresh)->toBeInstanceOf(WeatherData::class)
        ->and($fresh->city)->toBe('New York');

    $stale = $service->getStale('New York');
    expect($stale)->toBeInstanceOf(WeatherData::class)
        ->and($stale->city)->toBe('New York');
});

it('expires stale cache when ttl configured', function () {
    config([
        'weather.cache.prefix' => 'weather',
        'weather.cache.fresh_ttl' => 60,
        'weather.cache.stale_ttl' => 120,
    ]);

    Carbon::setTestNow('2025-01-01 00:00:00');

    $service = new WeatherCacheService();
    $data = new WeatherData(
        city: 'Chicago',
        temperature: -1.5,
        description: 'Snow',
        source: 'OpenWeather',
        fetched_at: Carbon::now(),
    );

    $service->store($data);

    Carbon::setTestNow('2025-01-01 00:01:01');
    expect($service->getFresh('Chicago'))->toBeNull();
    expect($service->getStale('Chicago'))->not()->toBeNull();

    Carbon::setTestNow('2025-01-01 00:02:01');
    expect($service->getStale('Chicago'))->toBeNull();
});
