<?php

use App\Services\Weather\HealthRegistry\ProviderHealthRegistryService;
use App\Services\Weather\Providers\BaseWeatherProviderService;
use Illuminate\Support\Facades\Cache;

it('trips circuit after configured failure threshold', function () {
    config([
        'cache.default' => 'array',
        'weather.provider_health.failure_threshold' => 2,
        'weather.provider_health.ttl' => 60,
    ]);

    $provider = $this->createMock(BaseWeatherProviderService::class);
    $provider->method('getName')->willReturn('Failing');

    $registry = new ProviderHealthRegistryService(Cache::store());

    expect($registry->isDown($provider))->toBeFalse();

    $registry->reportFailure($provider);
    expect($registry->isDown($provider))->toBeFalse();

    $registry->reportFailure($provider);
    expect($registry->isDown($provider))->toBeTrue();
});
