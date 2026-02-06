<?php

namespace App\Data;

use App\Enums\TemperatureUnit;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Symfony\Contracts\Service\Attribute\Required;

class WeatherRequestData extends Data
{
    public function __construct(
        #[Required]
        public string $city,
        #[WithCast(TemperatureUnit::class)]
        public ?string $unit = TemperatureUnit::CELSIUS->value,
    ) {}
}
