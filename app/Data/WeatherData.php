<?php

namespace App\Data;

use App\Enums\TemperatureUnit;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class WeatherData extends Data
{
    public function __construct(
        public string $city,
        public float $temperature, // Always stored in Celsius
        public string $description,
        public string $source,
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s')]
        public Carbon $fetched_at,
    ) {}

    /**
     * Create a new WeatherData instance with the current timestamp.
     */
    public static function fromProvider(
        string $city,
        float $temperatureCelsius,
        string $description,
        string $source,
    ): self {
        return new self(
            city: $city,
            temperature: $temperatureCelsius,
            description: $description,
            source: $source,
            fetched_at: Carbon::now(),
        );
    }

    /**
     * Get temperature converted to the specified unit.
     */
    public function getTemperatureInUnit(TemperatureUnit $unit): float
    {
        return round($unit->convertFromCelsius($this->temperature), 1);
    }
}

