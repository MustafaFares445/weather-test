<?php

namespace App\Services\Weather\Providers;

use App\Data\WeatherData;
use App\Exceptions\WeatherProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WeatherStackProviderService extends BaseWeatherProviderService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('weather.providers.weatherstack.base_url');
        $this->apiKey = config('weather.providers.weatherstack.api_key');
        $this->timeout = config('weather.http.timeout', 5);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $city): WeatherData
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/current", [
                    'query' => $city,
                    'access_key' => $this->apiKey,
                    'units' => 'm', // Metric (Celsius)
                ]);

            if ($response->failed()) {
                throw WeatherProviderException::httpError(
                    provider: $this->getName(),
                    statusCode: $response->status(),
                    message: 'Request failed',
                );
            }

            $data = $response->json();

            if (isset($data['error'])) {
                throw WeatherProviderException::httpError(
                    provider: $this->getName(),
                    statusCode: $data['error']['code'] ?? 500,
                    message: $data['error']['info'] ?? 'Unknown error',
                );
            }

            return WeatherData::fromProvider(
                city: $data['location']['name'],
                temperatureCelsius: (float) $data['current']['temperature'],
                description: $data['current']['weather_descriptions'][0] ?? 'Unknown',
                source: $this->getName(),
            );
        } catch (ConnectionException $e) {
            throw WeatherProviderException::connectionError($this->getName(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'WeatherStack';
    }
}

