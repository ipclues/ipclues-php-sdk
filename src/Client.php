<?php

declare(strict_types=1);

namespace IPClues;

use IPClues\Throws\IPCluesException;

final class Client
{
    private readonly HttpClient $http;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.ipclues.com/v1/',
        int $timeout = 10,
    ) {
        $this->http = new HttpClient($apiKey, $baseUrl, $timeout);
    }

    public static function fromEnv(): self
    {
        $apiKey = getenv('IPCLUES_API_KEY');

        if (false === $apiKey || '' === $apiKey) {
            throw new IPCluesException(
                "'IPCLUES_API_KEY' environment variable is not set."
            );
        }

        return new self($apiKey);
    }

    public function lookup(string $ip): mixed
    {
        return $this->http->request('GET', 'lookup/ip/'.rawurlencode($ip));
    }
}
