<?php

use App\Data\WeatherData;
use App\Data\WeatherRequestData;
use App\Exceptions\AllProvidersFailedException;
use App\Jobs\RefreshWeatherCacheJob;
use App\Services\Weather\Cache\WeatherCacheService;
use App\Services\Weather\HealthRegistry\WeatherProviderPoolService;
use App\Services\Weather\WeatherService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;

it('returns fresh cache without calling providers', function () {
    $fresh = new WeatherData(
        city: 'Paris',
        temperature: 12.3,
        description: 'Clear',
        source: 'Cache',
        fetched_at: Carbon::parse('2025-01-01 08:00:00'),
    );

    $cache = $this->createMock(WeatherCacheService::class);
    $cache->expects($this->once())
        ->method('getFresh')
        ->with('Paris')
        ->willReturn($fresh);
    $cache->expects($this->never())->method('store');
    $cache->expects($this->never())->method('getStale');

    $providerPool = $this->createMock(WeatherProviderPoolService::class);
    $providerPool->expects($this->never())->method('fetchFirstSuccessful');

    $service = new WeatherService($cache, $providerPool);

    $result = $service->getWeatherData(new WeatherRequestData(city: 'Paris'));

    expect($result)->toBe($fresh);
});

it('short circuits on first provider success and dispatches cache refresh', function () {
    $data = new WeatherData(
        city: 'Rome',
        temperature: 20.2,
        description: 'Sunny',
        source: 'Provider1',
        fetched_at: Carbon::parse('2025-01-01 09:00:00'),
    );

    Bus::fake();

    $cache = $this->createMock(WeatherCacheService::class);
    $cache->expects($this->once())
        ->method('getFresh')
        ->with('Rome')
        ->willReturn(null);
    $cache->expects($this->never())->method('getStale');

    $providerPool = $this->createMock(WeatherProviderPoolService::class);
    $providerPool->expects($this->once())
        ->method('fetchFirstSuccessful')
        ->with($this->callback(fn (WeatherRequestData $request) => $request->city === 'Rome'))
        ->willReturn($data);

    $service = new WeatherService($cache, $providerPool);

    $result = $service->getWeatherData(new WeatherRequestData(city: 'Rome'));

    expect($result)->toBe($data);

    Bus::assertDispatched(RefreshWeatherCacheJob::class, function (RefreshWeatherCacheJob $job) use ($data) {
        return $job->data->city === $data->city;
    });
});

it('falls back to stale cache when providers fail', function () {
    $stale = new WeatherData(
        city: 'Madrid',
        temperature: 15.1,
        description: 'Cloudy',
        source: 'Cache',
        fetched_at: Carbon::parse('2025-01-01 07:00:00'),
    );

    $cache = $this->createMock(WeatherCacheService::class);
    $cache->expects($this->once())
        ->method('getFresh')
        ->with('Madrid')
        ->willReturn(null);
    $cache->expects($this->once())
        ->method('getStale')
        ->with('Madrid')
        ->willReturn($stale);
    $cache->expects($this->never())->method('store');

    $providerPool = $this->createMock(WeatherProviderPoolService::class);
    $providerPool->expects($this->once())
        ->method('fetchFirstSuccessful')
        ->willReturn(null);

    $service = new WeatherService($cache, $providerPool);

    $result = $service->getWeatherData(new WeatherRequestData(city: 'Madrid'));

    expect($result)->toBe($stale);
});

it('throws when all providers fail and no cache', function () {
    $cache = $this->createMock(WeatherCacheService::class);
    $cache->expects($this->once())
        ->method('getFresh')
        ->with('Oslo')
        ->willReturn(null);
    $cache->expects($this->once())
        ->method('getStale')
        ->with('Oslo')
        ->willReturn(null);

    $providerPool = $this->createMock(WeatherProviderPoolService::class);
    $providerPool->expects($this->once())
        ->method('fetchFirstSuccessful')
        ->willReturn(null);

    $service = new WeatherService($cache, $providerPool);

    $this->expectException(AllProvidersFailedException::class);

    $service->getWeatherData(new WeatherRequestData(city: 'Oslo'));
});
