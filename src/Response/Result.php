<?php

declare(strict_types=1);

namespace IPClues\Sdk\Response;

final class Result
{
    public function __construct(
        public readonly mixed $data,
        public readonly Metadata $metadata,
    ) {}
}
