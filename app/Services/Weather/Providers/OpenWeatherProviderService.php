<?php

namespace App\Services\Weather\Providers;

use App\Data\WeatherData;
use App\Exceptions\WeatherProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenWeatherProviderService extends BaseWeatherProviderService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('weather.providers.openweather.base_url');
        $this->apiKey = config('weather.providers.openweather.api_key');
        $this->timeout = config('weather.http.timeout', 5);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $city): WeatherData
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/weather", [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric', // Always fetch in Celsius
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
            throw WeatherProviderException::connectionError($this->getName(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'OpenWeather';
    }
}

