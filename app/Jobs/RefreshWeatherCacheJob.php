<?php

namespace App\Jobs;

use App\Data\WeatherData;
use App\Services\Weather\Cache\WeatherCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshWeatherCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly WeatherData $data) {}

    public function handle(WeatherCacheService $cache): void
    {
        $cache->store($this->data);
    }
}
