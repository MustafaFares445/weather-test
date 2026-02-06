<?php

namespace App\Http\Resources;

use App\Data\WeatherData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WeatherData */
class WeatherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'city' => $this->city,
            'temperature' => $this->temperature,
            'unit' => 'Celsius',
            'description' => $this->description,
            'source' => $this->source,
            'fetchedAt' => $this->fetched_at->format('Y-m-d H:i:s'),
        ];
    }
}
