<?php

use App\Data\WeatherData;
use App\Exceptions\WeatherProviderException;
use App\Http\Controllers\WeatherController;
use App\Services\Weather\Cache\WeatherCacheService;
use App\Services\Weather\Providers\BaseWeatherProviderService;
use App\Services\Weather\WeatherService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/weather', WeatherController::class);

    $cache = $this->createMock(WeatherCacheService::class);
    $this->app->instance(WeatherService::class, new WeatherService([], $cache));
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
    $cache->expects($this->once())
        ->method('store')
        ->with($data);
    $cache->expects($this->never())->method('getStale');

    $provider = $this->createMock(BaseWeatherProviderService::class);
    $provider->expects($this->once())
        ->method('fetch')
        ->with('Paris')
        ->willReturn($data);
    $provider->method('getName')->willReturn('OpenWeather');

    $service = new WeatherService([$provider], $cache);
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

    $provider = $this->createMock(BaseWeatherProviderService::class);
    $provider->expects($this->never())->method('fetch');

    $service = new WeatherService([$provider], $cache);
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

    $provider = $this->createMock(BaseWeatherProviderService::class);
    $provider->expects($this->once())
        ->method('fetch')
        ->with('Paris')
        ->willThrowException(WeatherProviderException::httpError('Provider', 500));
    $provider->method('getName')->willReturn('Provider');

    $service = new WeatherService([$provider], $cache);
    $this->app->instance(WeatherService::class, $service);

    $response = $this->getJson('/weather?city=Paris');

    $response->assertStatus(503);
    $response->assertJson([
        'status' => 'error',
        'message' => 'Weather data is temporarily unavailable. Please try again later.',
        'city' => 'Paris',
    ]);
});
