<?php

namespace App\Services\Weather\Providers;

use App\Data\WeatherData;
use App\Exceptions\WeatherProviderException;

abstract class BaseWeatherProviderService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    /**
     * Fetch weather data for a given city.
     *
     * @param string $city The city name to fetch weather for
     * @return WeatherData The normalized weather data
     * @throws WeatherProviderException When the provider fails
     */
    abstract public function fetch(string $city): WeatherData;

    /**
     * Get the provider's name.
     *
     * @return string The provider identifier
     */
    abstract public function getName(): string;
}
