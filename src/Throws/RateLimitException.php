<?php

declare(strict_types=1);

namespace IPClues\Throws;

final class RateLimitException extends IPCluesException
{
    public function __construct(
        string $message = 'Rate limit exceeded.',
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, 429);
    }
}
