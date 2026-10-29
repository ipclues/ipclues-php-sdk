<?php

declare(strict_types=1);

namespace IPClues\Sdk;

use IPClues\Sdk\Throws\IPCluesException;

final class HttpClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://api.ipclues.com/v1/',
        private readonly int $timeout = 10,
    ) {
        if ('' === $this->apiKey) {
            throw new IPCluesException('API key cannot be empty.');
        }

        if ($this->timeout < 1) {
            throw new IPCluesException(
                'Timeout must be at least one second.'
            );
        }

        if (!extension_loaded('curl')) {
            throw new IPCluesException(
                'The PHP cURL extension is required.'
            );
        }
    }

    public function request(
        string $method,
        string $path,
        array $query = [],
        ?array $body = null,
    ): mixed {
        $url = rtrim($this->baseUrl, '/')
            .'/'
            .ltrim($path, '/');

        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }

        $headers = [
            'Accept: application/json',
            "Authorization: Bearer {$this->apiKey}",
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if (null !== $body) {
            $json = json_encode(
                $body,
                JSON_THROW_ON_ERROR
            );

            $options[CURLOPT_POSTFIELDS] = $json;
            $options[CURLOPT_HTTPHEADER][]
                = 'Content-Type: application/json';
        }

        $curl = curl_init();

        if (false === $curl) {
            throw new IPCluesException(
                'Failed to initialize HTTP client.'
            );
        }

        try {
            curl_setopt_array($curl, $options);

            $rawResponse = curl_exec($curl);

            if (false === $rawResponse) {
                throw new IPCluesException(
                    'HTTP request failed: '.curl_error($curl),
                    curl_errno($curl),
                );
            }

            $statusCode = curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE
            );

            $retryAfterHeader = curl_getinfo(
                $curl,
                CURLINFO_HEADER_OUT
            );

            $response = null;

            if ('' !== $rawResponse) {
                try {
                    $response = json_decode(
                        $rawResponse,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
                } catch (\JsonException) {
                    if ($statusCode >= 200 && $statusCode < 300) {
                        throw new IPCluesException(
                            'API returned invalid JSON.'
                        );
                    }

                    $response = $rawResponse;
                }
            }

            if (401 === $statusCode || 403 === $statusCode) {
                throw new IPCluesException(
                    'API authentication failed.',
                    $statusCode,
                );
            }

            if (429 === $statusCode) {
                throw new IPCluesException(
                    'API rate limit exceeded.',
                    $statusCode,
                );
            }

            if ($statusCode < 200 || $statusCode >= 300) {
                $message = \is_array($response)
                    ? ($response['message'] ?? 'API request failed.')
                    : 'API request failed.';

                throw new IPCluesException(
                    \is_string($message)
                        ? $message
                        : 'API request failed.',
                    $statusCode,
                    $response,
                );
            }

            return $response;
        } finally {
            curl_close($curl);
        }
    }
}
