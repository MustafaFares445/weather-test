<?php

use App\Data\WeatherData;
use App\Enums\TemperatureUnit;
use Carbon\Carbon;

it('converts temperature in requested unit', function () {
    $data = new WeatherData(
        city: 'Paris',
        temperature: 0.0,
        description: 'Clear',
        source: 'OpenWeather',
        fetched_at: Carbon::parse('2025-01-01 00:00:00'),
    );

    expect($data->getTemperatureInUnit(TemperatureUnit::CELSIUS))->toBe(0.0)
        ->and($data->getTemperatureInUnit(TemperatureUnit::FAHRENHEIT))->toBe(32.0);
});

it('rounds temperature conversion to one decimal', function () {
    $data = new WeatherData(
        city: 'Rome',
        temperature: 37.0,
        description: 'Hot',
        source: 'OpenWeather',
        fetched_at: Carbon::parse('2025-01-01 00:00:00'),
    );

    expect($data->getTemperatureInUnit(TemperatureUnit::FAHRENHEIT))->toBe(98.6);
});

it('sets fetched_at to now from provider', function () {
    Carbon::setTestNow('2025-01-02 12:34:56');

    $data = WeatherData::fromProvider(
        city: 'Lisbon',
        temperatureCelsius: 18.2,
        description: 'Clear',
        source: 'OpenWeather',
    );

    expect($data->fetched_at->format('Y-m-d H:i:s'))->toBe('2025-01-02 12:34:56');

    Carbon::setTestNow();
});
