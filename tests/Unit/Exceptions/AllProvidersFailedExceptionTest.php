<?php

use App\Exceptions\AllProvidersFailedException;

it('renders json response for all providers failed', function () {
    $exception = new AllProvidersFailedException('Tokyo');

    $response = $exception->render();

    expect($response->getStatusCode())->toBe(503)
        ->and($response->getData(true))->toBe([
            'status' => 'error',
            'message' => 'Weather data is temporarily unavailable. Please try again later.',
            'city' => 'Tokyo',
        ])
        ->and($exception->getMessage())->toBe('All weather providers failed for city: Tokyo');
});
