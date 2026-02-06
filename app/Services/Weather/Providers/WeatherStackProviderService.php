<?php

namespace App\Services\Weather\Providers;

use App\Data\WeatherData;
use App\Exceptions\WeatherProviderException;
use Illuminate\Http\Client\ConnectionException;

/**
 * WeatherStack provider implementation.
 */
final class WeatherStackProviderService extends BaseWeatherProviderService
{
    public function __construct()
    {
        parent::__construct(
            baseUrl: config('weather.providers.weatherstack.base_url'),
            apiKey: config('weather.providers.weatherstack.api_key'),
            timeout: config('weather.http.timeout', 5),
        );
    }

    public function fetch(string $city): WeatherData
    {
        try {
            $response = $this->http()->get('/current', [
                'query'      => $city,
                'access_key' => $this->apiKey,
                'units'      => 'm',
            ]);

            $data = $response->json();

            if (isset($data['error'])) {
                throw WeatherProviderException::httpError(
                    provider: $this->getName(),
                    statusCode: (int) ($data['error']['code'] ?? $response->status()),
                    message: $data['error']['info'] ?? 'Request failed',
                );
            }

            if ($response->failed()) {
                throw WeatherProviderException::httpError(
                    provider: $this->getName(),
                    statusCode: $response->status(),
                    message: 'Request failed',
                );
            }

            return WeatherData::fromProvider(
                city: $data['location']['name'],
                temperatureCelsius: (float) $data['current']['temperature'],
                description: $data['current']['weather_descriptions'][0] ?? 'Unknown',
                source: $this->getName(),
            );
        } catch (ConnectionException $e) {
            $this->handleConnectionException($e);
        }
    }

    public function getName(): string
    {
        return 'WeatherStack';
    }
}
