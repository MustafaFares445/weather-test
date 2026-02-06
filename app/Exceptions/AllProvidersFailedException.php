<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class AllProvidersFailedException extends Exception
{
    public function __construct(
        public readonly string $city,
        string $message = '',
    ) {
        parent::__construct(
            $message ?: "All weather providers failed for city: {$city}"
        );
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Weather data is temporarily unavailable. Please try again later.',
            'city' => $this->city,
        ], 503);
    }
}

