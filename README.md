# Weather Test – Multi‑Provider Weather Aggregator

Laravel project that aggregates weather data from multiple providers with caching, health‑aware failover, and a small REST endpoint. The core goal: always return the freshest possible weather for a city while degrading gracefully when providers misbehave.

## Quick Start
- Clone and install: `composer install`
- Copy env: `cp .env.example .env` then set API keys  
  - `OPENWEATHER_API_KEY`, `OPENWEATHER_BASE_URL` (default `https://api.openweathermap.org/data/2.5`)  
  - `WEATHERSTACK_API_KEY`, `WEATHERSTACK_BASE_URL` (default `http://api.weatherstack.com`)
- Optional tuning (in `config/weather.php`): cache TTLs, provider failure threshold/TTL, HTTP timeout.
- Expose the endpoint (once) by adding a route, e.g. in `routes/api.php`:  
  `Route::get('/weather', \\App\\Http\\Controllers\\WeatherController::class);`
- Run dev server: `php artisan serve`
- Hit the endpoint: `GET /weather?city=Paris&unit=fahrenheit`

## API
`GET /weather`
- **Query**:  
  - `city` (required, string, min 3)  
  - `unit` (optional, `celsius` | `fahrenheit`, default `celsius`)
- **Success 200** (example)
```json
{
  "data": {
    "city": "Paris",
    "temperature": 72.3,
    "unit": "Fahrenheit",
    "description": "Clear sky",
    "source": "OpenWeather",
    "fetchedAt": "2026-02-06 18:30:00"
  }
}
```
- **All providers down 503**
```json
{
  "status": "error",
  "message": "Weather data is temporarily unavailable. Please try again later.",
  "city": "Paris"
}
```

## How the Request Flows
1) `WeatherController` (`app/Http/Controllers/WeatherController.php`) validates the request (`WeatherRequest`).  
2) `WeatherService` (`app/Services/Weather/WeatherService.php`) orchestrates retrieval:  
   - Looks in the **fresh cache** first (`WeatherCacheService::getFresh`).  
   - If missing, asks the **provider pool** for data.  
   - On success, dispatches `RefreshWeatherCacheJob` to write fresh+stale cache.  
   - If all providers fail, serves **stale cache** as a fallback.  
   - If nothing available, throws `AllProvidersFailedException` → 503 response.  
3) `WeatherResource` shapes the JSON response.

## Providers & Failover
- Current providers: `OpenWeatherProviderService`, `WeatherStackProviderService` (both extend `BaseWeatherProviderService`).
- Provider pool (`WeatherProviderPoolService`) receives an ordered, tagged list (`weather.providers`) from `WeatherServiceProvider`. It:
  - Skips providers marked as unhealthy.
  - Tries each provider in order, returning on first success.
  - On failure, records it and moves on.

### Circuit‑Breaker Lite
- `ProviderHealthRegistryService` tracks consecutive failures per provider in cache.
- A provider is considered **down** once its failure count ≥ `weather.provider_health.failure_threshold` (default 3).  
- Counters expire after `weather.provider_health.ttl` seconds (default 300), allowing providers to recover.

## Caching Strategy
- Service: `WeatherCacheService` (`app/Services/Weather/Cache`).  
- **Fresh cache**: short‑lived (default 30 min) used for fast paths.  
- **Stale cache**: long‑lived (default infinite) used only when all providers fail.  
- Cache keys are slugged city names: `weather:fresh:{city}` and `weather:stale:{city}`.  
- Refresh flow: when a provider succeeds, `RefreshWeatherCacheJob` stores both fresh and stale entries.

## Configuration Cheatsheet (`config/weather.php`)
- `providers.*.base_url` / `api_key` — API credentials.
- `http.timeout` — request timeout (seconds).
- `cache.fresh_ttl` — fresh cache TTL (seconds).
- `cache.stale_ttl` — stale TTL (`null` = forever).
- `provider_health.failure_threshold` — failures before a provider is skipped.
- `provider_health.ttl` — failure counter TTL (seconds).

## Adding Another Provider
1) Create `app/Services/Weather/Providers/MyProviderService.php` extending `BaseWeatherProviderService`. Implement:
   - `fetch(string $city): WeatherData` — make the HTTP call and map to `WeatherData::fromProvider`.
   - `getName(): string` — human label used in logs/health keys.
2) Add its config under `config/weather.php` and env vars as needed.
3) Register and tag it in `app/Providers/WeatherServiceProvider.php` (`$this->app->singleton(...)` and add to the `weather.providers` tag array).
4) Order inside the tag array sets priority (earlier = higher).

## Development & Testing
- Queue: `RefreshWeatherCacheJob` uses Laravel queues; with `QUEUE_CONNECTION=sync` it runs inline, otherwise start a worker (`php artisan queue:work`).
- Tests: `php artisan test` (Pest). Feature coverage in `tests/Feature/WeatherEndpointTest.php` exercises validation, happy path, cache hit, and full failure.

## File Map (orientation)
- Request/response: `app/Http/Requests/WeatherRequest.php`, `app/Http/Resources/WeatherResource.php`
- Orchestration: `app/Services/Weather/WeatherService.php`
- Provider pool & health: `app/Services/Weather/HealthRegistry/*`
- Providers: `app/Services/Weather/Providers/*`
- Cache: `app/Services/Weather/Cache/*`
- Job: `app/Jobs/RefreshWeatherCacheJob.php`
- Config: `config/weather.php`
