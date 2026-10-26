<?php

declare(strict_types=1);

namespace IPClues\Throws;

final class ApiException extends IPCluesException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly mixed $response = null,
    ) {
        parent::__construct($message, $statusCode);
    }
}
