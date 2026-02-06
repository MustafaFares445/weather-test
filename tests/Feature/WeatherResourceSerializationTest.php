<?php

use App\Data\WeatherData;
use App\Http\Resources\WeatherResource;
use Carbon\Carbon;
use Illuminate\Http\Request;

it('serializes weather data resource', function () {
    $data = new WeatherData(
        city: 'Lisbon',
        temperature: 18.4,
        description: 'Clear',
        source: 'OpenWeather',
        fetched_at: Carbon::parse('2025-01-03 14:30:45'),
    );

    $payload = WeatherResource::make($data)->toArray(Request::create('/'));

    expect($payload)->toBe([
        'city' => 'Lisbon',
        'temperature' => 18.4,
        'unit' => 'Celsius',
        'description' => 'Clear',
        'source' => 'OpenWeather',
        'fetchedAt' => '2025-01-03 14:30:45',
    ]);
});
