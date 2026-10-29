<?php

declare(strict_types=1);

namespace IPClues\Sdk\Response;

final class RateLimitMetadata
{
    public function __construct(
        public readonly ?string $policy = null,
        public readonly ?int $limit = null,
        public readonly ?int $remaining = null,
        public readonly ?int $reset = null,
        public readonly ?int $retryAfter = null,
    ) {}
}
