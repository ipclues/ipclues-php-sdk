<?php

declare(strict_types=1);

namespace IPClues\Sdk\Response;

final class Metadata
{
    /**
     * @param null|list<string>          $databases
     * @param null|array<string, string> $databaseVersions
     */
    public function __construct(
        public readonly int $status,
        public readonly string $statusText,
        public readonly ?string $requestId,
        public readonly ?int $creditsCharged,
        public readonly ?int $creditsRemaining,
        public readonly ?array $databases,
        public readonly ?array $databaseVersions,
        public readonly RateLimitMetadata $rateLimit,
    ) {}
}
