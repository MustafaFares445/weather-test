<?php

namespace App\Services\Weather\Providers;

use App\Exceptions\WeatherProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Base class for HTTP-based weather providers.
 *
 * Responsibilities:
 * - Configure HTTP client (base URL, timeout)
 * - Normalize connection-level failures
 * - Provide a consistent execution surface for providers
 *
 * Providers extending this class should focus exclusively on:
 * - Endpoint paths
 * - Request parameters
 * - Response mapping
 */
abstract class BaseWeatherProviderService
{
    public function __construct(
        protected readonly string $baseUrl,
        protected readonly string $apiKey,
        protected readonly int $timeout,
    ) {}

    /**
     * Fetch weather data for the given city.
     *
     * @throws WeatherProviderException
     */
    abstract public function fetch(string $city): \App\Data\WeatherData;

    /**
     * Human-readable provider identifier.
     */
    abstract public function getName(): string;

    /**
     * Prepare a preconfigured HTTP client for the provider.
     */
    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson();
    }

    /**
     * Wrap connection-level exceptions consistently.
     *
     * @throws WeatherProviderException
     */
    protected function handleConnectionException(ConnectionException $exception): void
    {
        throw WeatherProviderException::connectionError(
            provider: $this->getName(),
            previous: $exception,
        );
    }
}
