<?php

namespace App\Services\Weather\Providers;

use App\Data\WeatherData;
use App\Exceptions\WeatherProviderException;
use Illuminate\Http\Client\ConnectionException;

/**
 * OpenWeather provider implementation.
 */
final class OpenWeatherProviderService extends BaseWeatherProviderService
{
    public function __construct()
    {
        parent::__construct(
            baseUrl: config('weather.providers.openweather.base_url'),
            apiKey: config('weather.providers.openweather.api_key'),
            timeout: config('weather.http.timeout', 5),
        );
    }

    public function fetch(string $city): WeatherData
    {
        try {
            $response = $this->http()->get('/weather', [
                'q'     => $city,
                'appid' => $this->apiKey,
                'units' => 'metric',
            ]);

            if ($response->failed()) {
                throw WeatherProviderException::httpError(
                    provider: $this->getName(),
                    statusCode: $response->status(),
                    message: $response->json('message', 'Unknown error'),
                );
            }

            $data = $response->json();

            return WeatherData::fromProvider(
                city: $data['name'],
                temperatureCelsius: (float) $data['main']['temp'],
                description: $data['weather'][0]['description'] ?? 'Unknown',
                source: $this->getName(),
            );
        } catch (ConnectionException $e) {
            $this->handleConnectionException($e);
        }
    }

    public function getName(): string
    {
        return 'OpenWeather';
    }
}
