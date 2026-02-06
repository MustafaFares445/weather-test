<?php

use App\Data\WeatherData;
use App\Data\WeatherRequestData;
use App\Exceptions\AllProvidersFailedException;
use App\Exceptions\WeatherProviderException;
use App\Services\Weather\Cache\WeatherCacheService;
use App\Services\Weather\Providers\BaseWeatherProviderService;
use App\Services\Weather\WeatherService;
use Carbon\Carbon;

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

    $provider = $this->createMock(BaseWeatherProviderService::class);
    $provider->expects($this->never())->method('fetch');

    $service = new WeatherService([$provider], $cache);

    $result = $service->getWeatherData(new WeatherRequestData(city: 'Paris'));

    expect($result)->toBe($fresh);
});

it('short circuits on first provider success and caches', function () {
    $data = new WeatherData(
        city: 'Rome',
        temperature: 20.2,
        description: 'Sunny',
        source: 'Provider1',
        fetched_at: Carbon::parse('2025-01-01 09:00:00'),
    );

    $cache = $this->createMock(WeatherCacheService::class);
    $cache->expects($this->once())
        ->method('getFresh')
        ->with('Rome')
        ->willReturn(null);
    $cache->expects($this->once())
        ->method('store')
        ->with($data);
    $cache->expects($this->never())->method('getStale');

    $provider1 = $this->createMock(BaseWeatherProviderService::class);
    $provider1->expects($this->once())
        ->method('fetch')
        ->with('Rome')
        ->willReturn($data);
    $provider1->method('getName')->willReturn('Provider1');

    $provider2 = $this->createMock(BaseWeatherProviderService::class);
    $provider2->expects($this->never())->method('fetch');

    $service = new WeatherService([$provider1, $provider2], $cache);

    $result = $service->getWeatherData(new WeatherRequestData(city: 'Rome'));

    expect($result)->toBe($data);
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

    $provider = $this->createMock(BaseWeatherProviderService::class);
    $provider->expects($this->once())
        ->method('fetch')
        ->with('Madrid')
        ->willThrowException(WeatherProviderException::httpError('Provider', 500));
    $provider->method('getName')->willReturn('Provider');

    $service = new WeatherService([$provider], $cache);

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

    $provider = $this->createMock(BaseWeatherProviderService::class);
    $provider->expects($this->once())
        ->method('fetch')
        ->with('Oslo')
        ->willThrowException(WeatherProviderException::httpError('Provider', 500));
    $provider->method('getName')->willReturn('Provider');

    $service = new WeatherService([$provider], $cache);

    $this->expectException(AllProvidersFailedException::class);

    $service->getWeatherData(new WeatherRequestData(city: 'Oslo'));
});
