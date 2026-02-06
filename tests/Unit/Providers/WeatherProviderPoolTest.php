<?php

use App\Data\WeatherData;
use App\Data\WeatherRequestData;
use App\Exceptions\WeatherProviderException;
use App\Services\Weather\HealthRegistry\ProviderHealthRegistryService;
use App\Services\Weather\HealthRegistry\WeatherProviderPoolService;
use App\Services\Weather\Providers\BaseWeatherProviderService;
use Carbon\Carbon;

it('skips unhealthy providers and returns first successful', function () {
    $request = new WeatherRequestData(city: 'Paris');

    $healthyProvider = $this->createMock(BaseWeatherProviderService::class);
    $healthyProvider->method('getName')->willReturn('Healthy');
    $healthyProvider->expects($this->once())
        ->method('fetch')
        ->with('Paris')
        ->willReturn(
            new WeatherData(
                city: 'Paris',
                temperature: 21.5,
                description: 'Sunny',
                source: 'Healthy',
                fetched_at: Carbon::parse('2025-01-01 10:00:00'),
            ),
        );

    $unhealthyProvider = $this->createMock(BaseWeatherProviderService::class);
    $unhealthyProvider->method('getName')->willReturn('Down');
    $unhealthyProvider->expects($this->never())->method('fetch');

    $health = $this->createMock(ProviderHealthRegistryService::class);
    $health->expects($this->exactly(2))
        ->method('isDown')
        ->willReturnCallback(fn ($provider) => $provider === $unhealthyProvider);
    $health->expects($this->never())->method('reportFailure');

    $pool = new WeatherProviderPoolService([$unhealthyProvider, $healthyProvider], $health);

    $result = $pool->fetchFirstSuccessful($request);

    expect($result)->not()->toBeNull()
        ->and($result?->source)->toBe('Healthy');
});

it('reports failures and returns null when all providers fail', function () {
    $request = new WeatherRequestData(city: 'Rome');

    $provider1 = $this->createMock(BaseWeatherProviderService::class);
    $provider1->method('getName')->willReturn('P1');
    $provider1->expects($this->once())
        ->method('fetch')
        ->with('Rome')
        ->willThrowException(WeatherProviderException::httpError('P1', 500));

    $provider2 = $this->createMock(BaseWeatherProviderService::class);
    $provider2->method('getName')->willReturn('P2');
    $provider2->expects($this->once())
        ->method('fetch')
        ->with('Rome')
        ->willThrowException(WeatherProviderException::httpError('P2', 500));

    $health = $this->createMock(ProviderHealthRegistryService::class);
    $health->expects($this->exactly(2))
        ->method('isDown')
        ->willReturn(false);
    $health->expects($this->exactly(2))
        ->method('reportFailure');

    $pool = new WeatherProviderPoolService([$provider1, $provider2], $health);

    $result = $pool->fetchFirstSuccessful($request);

    expect($result)->toBeNull();
});
