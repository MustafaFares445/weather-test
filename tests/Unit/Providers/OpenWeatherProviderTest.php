<?php

use App\Exceptions\WeatherProviderException;
use App\Services\Weather\Providers\OpenWeatherProviderService;
use Illuminate\Support\Facades\Http;

it('parses openweather response', function () {
    config([
        'weather.providers.openweather.base_url' => 'https://openweather.test',
        'weather.providers.openweather.api_key' => 'test-key',
        'weather.http.timeout' => 5,
    ]);

    Http::fake([
        'https://openweather.test/*' => Http::response([
            'name' => 'Paris',
            'main' => ['temp' => 20.5],
            'weather' => [['description' => 'sunny']],
        ], 200),
    ]);

    $provider = new OpenWeatherProviderService();

    $data = $provider->fetch('Paris');

    expect($data->city)->toBe('Paris')
        ->and($data->temperature)->toBe(20.5)
        ->and($data->description)->toBe('sunny')
        ->and($data->source)->toBe('OpenWeather');

    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'https://openweather.test/weather')
            && $request['q'] === 'Paris'
            && $request['appid'] === 'test-key'
            && $request['units'] === 'metric';
    });
});

it('throws when openweather fails', function () {
    config([
        'weather.providers.openweather.base_url' => 'https://openweather.test',
        'weather.providers.openweather.api_key' => 'test-key',
        'weather.http.timeout' => 5,
    ]);

    Http::fake([
        'https://openweather.test/*' => Http::response(['message' => 'Invalid'], 500),
    ]);

    $provider = new OpenWeatherProviderService();

    try {
        $provider->fetch('Paris');
        $this->fail('Expected WeatherProviderException was not thrown.');
    } catch (WeatherProviderException $e) {
        expect($e->provider)->toBe('OpenWeather')
            ->and($e->statusCode)->toBe(500);
    }
});
