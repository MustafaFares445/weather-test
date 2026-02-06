<?php

use App\Exceptions\WeatherProviderException;
use App\Services\Weather\Providers\WeatherStackProviderService;
use Illuminate\Support\Facades\Http;

it('parses weatherstack response', function () {
    config([
        'weather.providers.weatherstack.base_url' => 'http://weatherstack.test',
        'weather.providers.weatherstack.api_key' => 'test-key',
        'weather.http.timeout' => 5,
    ]);

    Http::fake([
        'http://weatherstack.test/*' => Http::response([
            'location' => ['name' => 'Berlin'],
            'current' => [
                'temperature' => 8.2,
                'weather_descriptions' => ['Cloudy'],
            ],
        ], 200),
    ]);

    $provider = new WeatherStackProviderService();

    $data = $provider->fetch('Berlin');

    expect($data->city)->toBe('Berlin')
        ->and($data->temperature)->toBe(8.2)
        ->and($data->description)->toBe('Cloudy')
        ->and($data->source)->toBe('WeatherStack');

    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'http://weatherstack.test/current')
            && $request['query'] === 'Berlin'
            && $request['access_key'] === 'test-key'
            && $request['units'] === 'm';
    });
});

it('throws when weatherstack returns error payload', function () {
    config([
        'weather.providers.weatherstack.base_url' => 'http://weatherstack.test',
        'weather.providers.weatherstack.api_key' => 'test-key',
        'weather.http.timeout' => 5,
    ]);

    Http::fake([
        'http://weatherstack.test/*' => Http::response([
            'error' => ['code' => 101, 'info' => 'Invalid key'],
        ], 200),
    ]);

    $provider = new WeatherStackProviderService();

    try {
        $provider->fetch('Berlin');
        $this->fail('Expected WeatherProviderException was not thrown.');
    } catch (WeatherProviderException $e) {
        expect($e->provider)->toBe('WeatherStack')
            ->and($e->statusCode)->toBe(101);
    }
});
