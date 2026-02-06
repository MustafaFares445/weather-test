<?php

namespace App\Exceptions;

use Exception;

class WeatherProviderException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $provider,
        public readonly ?int $statusCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Create exception for HTTP error.
     */
    public static function httpError(string $provider, int $statusCode, string $message = ''): self
    {
        return new self(
            message: $message ?: "Provider returned HTTP {$statusCode}",
            provider: $provider,
            statusCode: $statusCode,
        );
    }

    /**
     * Create exception for connection error.
     */
    public static function connectionError(string $provider, \Throwable $previous): self
    {
        return new self(
            message: "Failed to connect to provider: {$previous->getMessage()}",
            provider: $provider,
            previous: $previous,
        );
    }
}

