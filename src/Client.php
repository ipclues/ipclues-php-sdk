<?php

declare(strict_types=1);

namespace IPClues\Sdk;

use IPClues\Sdk\Response\Metadata as ResponseMetadata;
use IPClues\Sdk\Response\RateLimitMetadata;
use IPClues\Sdk\Response\Result as ResponseResult;
use IPClues\Sdk\Throws\IPCluesException;

final class Client
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $apiKey, string $baseUrl = 'https://api.ipclues.com/v1', int $timeout = 10)
    {
        if ('' === trim($apiKey)) {
            throw new \InvalidArgumentException('IPClues API key is required');
        }
        if ($timeout <= 0) {
            throw new \InvalidArgumentException('Timeout must be positive');
        }
        if (!extension_loaded('curl')) {
            throw new IPCluesException('PHP cURL extension is required');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * @param null|Database|list<Database> $db
     */
    public function lookup(string $ip, array|Database|null $db = null): ResponseResult
    {
        if ('' === trim($ip)) {
            throw new \InvalidArgumentException('IP address is required');
        }

        $databases = null === $db ? [] : ($db instanceof Database ? [$db] : $db);

        foreach ($databases as $database) {
            if (!$database instanceof Database) {
                throw new \InvalidArgumentException(
                    'Every database must be a Database enum'
                );
            }
        }

        if (null !== $db && [] === $databases) {
            throw new \InvalidArgumentException(
                'At least one database must be selected'
            );
        }

        $url = $this->baseUrl.'/lookup/ip/'.rawurlencode($ip);
        if ([] !== $databases) {
            $url .= '?'.http_build_query(
                ['db' => implode(',', array_map(
                    static fn (Database $item): string => $item->value,
                    $databases,
                ))],
            );
        }

        $headers = [];
        $statusText = '';

        $handle = curl_init($url);
        if (false === $handle) {
            throw new IPCluesException('Could not initialize HTTP request');
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiKey}",
                'Accept: application/json',
            ],
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (
                &$headers,
                &$statusText,
            ): int {
                $length = \strlen($line);
                $trimmed = trim($line);

                // Reset headers if a new HTTP response block starts.
                if (preg_match('/^HTTP\/\S+\s+\d+\s*(.*)$/i', $trimmed, $match)) {
                    $headers = [];
                    $statusText = $match[1] ?? '';
                } elseif (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($name))] = trim($value);
                }

                return $length;
            },
        ]);

        $body = curl_exec($handle);

        if (false === $body) {
            $message = curl_error($handle);
            curl_close($handle);

            throw new IPCluesException("IPClues request failed: {$message}");
        }

        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        $decoded = json_decode($body, true);
        $data = JSON_ERROR_NONE === json_last_error() ? $decoded : $body;

        $integer = fn (?string $value) => null !== $value && \preg_match('/^-?\d+$/', $value)
                ? (int) $value
                : null;

        $parseVersions = static function (?string $value): ?array {
            if (!$value) {
                return null;
            }

            $versions = [];
            foreach (explode(',', $value) as $item) {
                $parts = explode('=', trim($item), 2);
                if (2 === \count($parts) && '' !== $parts[0]) {
                    $versions[$parts[0]] = $parts[1];
                }
            }

            return $versions ?: null;
        };

        $rateHeader = $headers['ratelimit'] ?? '';
        $policyHeader = $headers['ratelimit-policy'] ?? '';

        preg_match('/^"?([^";]+)"?/', $rateHeader, $rateMatch);
        preg_match('/^"?([^";]+)"?/', $policyHeader, $policyMatch);
        preg_match('/(?:^|;)\s*r=(\d+)/', $rateHeader, $remainingMatch);
        preg_match('/(?:^|;)\s*t=(\d+)/', $rateHeader, $resetMatch);
        preg_match('/(?:^|;)\s*q=(\d+)/', $policyHeader, $limitMatch);

        $databasesHeader = $headers['i3-databases'] ?? null;

        $metadata = new ResponseMetadata(
            status: $status,
            statusText: $statusText,
            requestId: $headers['i3-request-id'] ?? null,
            creditsCharged: $integer($headers['i3-credits-charged'] ?? null),
            creditsRemaining: $integer($headers['i3-credits-remaining'] ?? null),
            databases: null !== $databasesHeader
                ? array_values(array_filter(array_map('trim', explode(',', $databasesHeader))))
                : null,
            databaseVersions: $parseVersions($headers['i3-database-versions'] ?? null),
            rateLimit: new RateLimitMetadata(
                policy: $rateMatch[1] ?? $policyMatch[1] ?? null,
                limit: isset($limitMatch[1]) ? (int) $limitMatch[1] : null,
                remaining: isset($remainingMatch[1]) ? (int) $remainingMatch[1] : null,
                reset: isset($resetMatch[1]) ? (int) $resetMatch[1] : null,
                retryAfter: $integer($headers['retry-after'] ?? null),
            ),
        );

        return new ResponseResult($data, $metadata);
    }
}
