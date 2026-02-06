<?php

namespace App\Http\Controllers;

use App\Data\WeatherRequestData;
use App\Exceptions\AllProvidersFailedException;
use App\Http\Requests\WeatherRequest;
use App\Http\Resources\WeatherResource;
use App\Services\Weather\WeatherService;

final readonly class WeatherController
{
    public function __construct(private WeatherService $weatherService) {}

    /**
     * @throws AllProvidersFailedException
     */
    public function __invoke(WeatherRequest $request)
    {
        $weatherData = $this->weatherService->getWeatherData(WeatherRequestData::from($request->validated()));

        return WeatherResource::make($weatherData);
    }
}
