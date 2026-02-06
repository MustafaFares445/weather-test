<?php

namespace App\Enums;

enum TemperatureUnit: string
{
    case CELSIUS = 'celsius';
    case FAHRENHEIT = 'fahrenheit';

    /**
     * Get the default temperature unit.
     */
    public static function default(): self
    {
        return self::CELSIUS;
    }

    /**
     * Convert a Celsius temperature to this unit.
     */
    public function convertFromCelsius(float $celsius): float
    {
        return match ($this) {
            self::CELSIUS => $celsius,
            self::FAHRENHEIT => ($celsius * 9 / 5) + 32,
        };
    }
}

