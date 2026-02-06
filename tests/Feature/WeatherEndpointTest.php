<?php

use App\Data\WeatherData;
use App\Http\Controllers\WeatherController;
use App\Jobs\RefreshWeatherCacheJob;
use App\Services\Weather\Cache\WeatherCacheService;
use App\Services\Weather\HealthRegistry\WeatherProviderPoolService;
use App\Services\Weather\WeatherService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/weather', WeatherController::class);

    $cache = $this->createMock(WeatherCacheService::class);
    $providerPool = $this->createMock(WeatherProviderPoolService::class);
    $this->app->instance(WeatherService::class, new WeatherService($cache, $providerPool));
});

it('validates missing city', function () {
    $response = $this->getJson('/weather');

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['city']);
});

it('validates invalid unit', function () {
    $response = $this->getJson('/weather?city=Paris&unit=kelvin');

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['unit']);
});

it('returns weather resource on success', function () {
    $data = new WeatherData(
        city: 'Paris',
        temperature: 21.5,
        description: 'Sunny',
        source: 'OpenWeather',
        fetched_at: Carbon::parse('2025-01-01 10:00:00'),
    );

    $cache = $this->createMock(WeatherCacheService::class);
    $cache->expects($this->once())
        ->method('getFresh')
        ->with('Paris')
        ->willReturn(null);
    $cache->expects($this->never())->method('getStale');

    $providerPool = $this->createMock(WeatherProviderPoolService::class);
    $providerPool->expects($this->once())
        ->method('fetchFirstSuccessful')
        ->willReturn($data);

    Bus::fake();

    $service = new WeatherService($cache, $providerPool);
    $this->app->instance(WeatherService::class, $service);

    $response = $this->getJson('/weather?city=Paris');

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'city' => 'Paris',
            'temperature' => 21.5,
            'unit' => 'Celsius',
            'description' => 'Sunny',
            'source' => 'OpenWeather',
            'fetchedAt' => '2025-01-01 10:00:00',
        ],
    ]);

    Bus::assertDispatched(RefreshWeatherCacheJob::class);
});

it('returns cached response when service uses cache', function () {
    $cached = new WeatherData(
        city: 'Berlin',
        temperature: 7.2,
        description: 'Cloudy',
        source: 'Cache',
        fetched_at: Carbon::parse('2025-01-02 09:15:00'),
    );

    $cache = $this->createMock(WeatherCacheService::class);
    $cache->expects($this->once())
        ->method('getFresh')
        ->with('Berlin')
        ->willReturn($cached);
    $cache->expects($this->never())->method('getStale');
    $cache->expects($this->never())->method('store');

    $providerPool = $this->createMock(WeatherProviderPoolService::class);
    $providerPool->expects($this->never())->method('fetchFirstSuccessful');

    $service = new WeatherService($cache, $providerPool);
    $this->app->instance(WeatherService::class, $service);

    $response = $this->getJson('/weather?city=Berlin');

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'city' => 'Berlin',
            'source' => 'Cache',
            'fetchedAt' => '2025-01-02 09:15:00',
        ],
    ]);
});

it('renders 503 when all providers fail', function () {
    $cache = $this->createMock(WeatherCacheService::class);
    $cache->expects($this->once())
        ->method('getFresh')
        ->with('Paris')
        ->willReturn(null);
    $cache->expects($this->once())
        ->method('getStale')
        ->with('Paris')
        ->willReturn(null);

    $providerPool = $this->createMock(WeatherProviderPoolService::class);
    $providerPool->expects($this->once())
        ->method('fetchFirstSuccessful')
        ->willReturn(null);

    $service = new WeatherService($cache, $providerPool);
    $this->app->instance(WeatherService::class, $service);

    $response = $this->getJson('/weather?city=Paris');

    $response->assertStatus(503);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Weather data is temporarily unavailable. Please try again later.',
        'city' => 'Paris',
    ]);
});
